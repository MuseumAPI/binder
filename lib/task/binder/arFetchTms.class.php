<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Fetch TMS data
 *
 * @package    symfony
 * @subpackage task
 */
class arFetchTms
{
  protected
    $tmsBaseUrl,
    $statusMapping,
    $componentLevels,
    $logger,
    $searchInstance;

  public function __construct()
  {
    $this->tmsBaseUrl = sfConfig::get('app_drmc_tms_url');

    // Mapping from TMS status to level of descriptions
    $this->statusMapping = array(
      'Archival'               => sfConfig::get('app_drmc_lod_archival_master_id'),
      'Archival submaster'     => sfConfig::get('app_drmc_lod_archival_master_id'),
      'Artist master'          => sfConfig::get('app_drmc_lod_artist_supplied_master_id'),
      'Artist proof'           => sfConfig::get('app_drmc_lod_artist_verified_proof_id'),
      'Duplication master'     => sfConfig::get('app_drmc_lod_component_id'),
      'Exhibition copy'        => sfConfig::get('app_drmc_lod_exhibition_format_id'),
      'Miscellaneous other'    => sfConfig::get('app_drmc_lod_miscellaneous_id'),
      'Repository File Source' => sfConfig::get('app_drmc_lod_component_id'),
      'Research copy'          => sfConfig::get('app_drmc_lod_component_id')
    );

    $this->componentLevels = array_unique(array_values($this->statusMapping));

    $this->logger = sfContext::getInstance()->getLogger();

    $this->searchInstance = QubitSearch::getInstance();
  }

  protected function getTmsData($path)
  {
    $data = null;
    $url = $this->tmsBaseUrl.$path;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FAILONERROR => true,
        CURLOPT_URL => $url));

    if (false === $resp = curl_exec($curl))
    {
      $this->logger->info('arFetchTms - Error getting Tombstone data: '.curl_error($curl));
      $this->logger->info('arFetchTms - URL: '.$url);
    }
    else
    {
      $data = json_decode($resp, true);
    }

    curl_close($curl);

    return $data;
  }

  public function getTmsObjectData($tmsObject, $tmsObjectId)
  {
    $tmsComponentsIds = $creation = array();
    $artworkThumbnail = null;

    // Request object from TMS API
    if (null !== $data = $this->getTmsData('/GetTombstoneDataRest/ObjectID/'.$tmsObjectId))
    {
      $data = $data['GetTombstoneDataRestIdResult'];

      foreach ($data as $name => $value)
      {
        if (!isset($value) || 0 == strlen($value))
        {
          continue;
        }

        switch ($name)
        {
          // Info. object fields
          case 'Dimensions':
            $tmsObject->physicalCharacteristics = $value;

            break;

          case 'Medium':
            $tmsObject->extentAndMedium = $value;

            break;

          case 'ObjectID':
            $tmsObject->identifier = $value;

            break;

          case 'Title':
            $tmsObject->title = $value;

            break;

          // Properties
          case 'AccessionISODate':
          case 'ClassificationID':
          case 'ConstituentID':
          case 'DepartmentID':
          case 'LastModifiedCheckDate':
          case 'ImageID':
          case 'ObjectNumber':
          case 'ObjectStatusID':
          case 'SortNumber':
            $this->addOrUpdateProperty($name, $value, $tmsObject);

            break;

          // Object/term relations
          case 'Classification':
          case 'Department':
            $this->addOrUpdateObjectTermRelation($name, $value, $tmsObject);

            break;

          // Creation event
          case 'Dated':
            $creation['date'] = $value;

            break;

          case 'DisplayName':
            $creation['actorName'] = $value;

            break;

          case 'DisplayDate':
            $creation['actorDate'] = $value;

            break;

          // Digital object
          case 'FullImage':
            // Encode filename in the URL
            $filename = basename(parse_url($value, PHP_URL_PATH));
            $value = str_replace($filename, rawurlencode($filename), $value);

            // Update digital object if exists
            if (null !== $digitalObject = $tmsObject->getDigitalObject())
            {
              $criteria = new Criteria;
              $criteria->add(QubitDigitalObject::PARENT_ID, $digitalObject->id);

              $children = QubitDigitalObject::get($criteria);

              // Delete derivatives
              foreach ($children as $child)
              {
                $child->delete();
              }

              // Import new one
              $digitalObject->importFromUri($value);
            }
            else
            {
              // Or create new one
              $errors = array();
              $tmsObject->importDigitalObjectFromUri($value, $errors);

              foreach ($errors as $error)
              {
                $this->logger->info('arFetchTms - '.$error);
              }
            }

            // Add property
            $this->addOrUpdateProperty($name, $value, $tmsObject);

            break;

          case 'Thumbnail':
            $artworkThumbnail = $value;
            $this->addOrUpdateProperty($name, $value, $tmsObject);

            break;

          // Child components
          case 'Components':
            foreach (json_decode($value, true) as $item)
            {
              $tmsComponentsIds[] = $item['ComponentID'];
            }

            break;

          // Log error
          case 'ErrorMsg':
            $this->logger->info('arFetchTms - ErrorMsg: '.$value);

            break;

          // Nothing yet
          case 'AlphaSort':
          case 'CreditLine':
          case 'FirstName':
          case 'LastName':
          case 'Prints':

            break;
        }
      }
    }

    $tmsObject->save();

    if (count($creation))
    {
      // Check for existing creation event
      if (isset($tmsObject->id))
      {
        $criteria = new Criteria;
        $criteria->add(QubitEvent::INFORMATION_OBJECT_ID, $tmsObject->id);
        $criteria->add(QubitEvent::TYPE_ID, QubitTerm::CREATION_ID);

        $creationEvent = QubitEvent::getOne($criteria);
      }

      // Or create new one
      if (!isset($creationEvent))
      {
        $creationEvent = new QubitEvent;
        $creationEvent->informationObjectId = $tmsObject->id;
        $creationEvent->typeId = QubitTerm::CREATION_ID;
      }

      $creationEvent->indexOnSave = false;

      // Add data
      qtSwordPlugin::addDataToCreationEvent($creationEvent, $creation);
    }

    return array($tmsComponentsIds, $artworkThumbnail);
  }

  public function getTmsComponentData($tmsComponent, $tmsComponentId, $artworkThumbnail)
  {
    // Request component from TMS API
    if (null !== $data = $this->getTmsData('/GetComponentDetails/Component/'.$tmsComponentId))
    {
      $data = $data['GetComponentDetailsResult'];

      // Attributes can have multiple items with the same label.
      // To avoid updating only the first property with that label
      // all the tms_attributes properties are deleted first
      foreach ($tmsComponent->getProperties(null, 'tms_attributes') as $property)
      {
        $property->indexOnSave = false;
        $property->delete();
      }

      foreach ($data as $name => $value)
      {
        if (empty($value))
        {
          continue;
        }

        switch ($name)
        {
          case 'Attributes':
            foreach (json_decode($value, true) as $item)
            {
              // Level of description from status attribute
              if (!empty($item['Status']) && isset($this->statusMapping[$item['Status']]))
              {
                $tmsComponent->levelOfDescriptionId = $this->statusMapping[$item['Status']];
              }

              // Add property for each attribute
              $count = 0;
              $propertyName = $propertyValue = null;
              foreach ($item as $key => $value)
              {
                if (empty($key) || empty($value))
                {
                  continue;
                }

                // Get property name from first key
                if ($count == 0)
                {
                  $propertyName = $key;
                  $propertyValue = $value;
                }
                else
                {
                  $propertyValue .= '. '.$key;
                  $propertyValue .= ': '.$value;
                }

                $count ++;
              }

              if (isset($propertyName) && isset($propertyValue))
              {
                $this->addOrUpdateProperty($propertyName, $propertyValue, $tmsComponent, array('scope' => 'tms_attributes'));
              }
            }

            break;

          // Info. object fields
          case 'ComponentID':
            $tmsComponent->identifier = $value;

            break;

          case 'ComponentName':
            $tmsComponent->title = $value;

            break;

          case 'Dimensions':
            $tmsComponent->physicalCharacteristics = $value;

            break;

          case 'PhysDesc':
            $tmsComponent->extentAndMedium = $value;

            break;

          // Properties
          case 'CompCount':
          case 'ComponentNumber':
            $this->addOrUpdateProperty($name, $value, $tmsComponent);

            break;

          // Object/term relation
          case 'ComponentType':
            $this->addOrUpdateObjectTermRelation('component_type', $value, $tmsComponent);

            break;

          // Notes
          case 'InstallComments':
          case 'PrepComments':
          case 'StorageComments':
            $this->addOrUpdateNote(sfConfig::get('app_drmc_term_'.strtolower($name).'_id'), $value, $tmsComponent);

            break;

          case 'TextEntries':
            $content = array();
            foreach (json_decode($value, true) as $textEntry)
            {
              $row = '';
              foreach ($textEntry as $field => $value)
              {
                if ($field == 'TextDate' && !empty($value))
                {
                  $row .= ', Date: '.$value;
                }
                else if ($field == 'TextAuthor' && !empty($value))
                {
                  $row .= ', Author: '.$value;
                }
                else if (!empty($field) && !empty($value))
                {
                  $row .= $field.': '.$value;
                }
              }

              $content[] = $row;
            }

            $this->addOrUpdateNote(QubitTerm::GENERAL_NOTE_ID, implode($content, "\n"), $tmsComponent);

            break;

          // Log error
          case 'ErrorMsg':
            $this->logger->info('arFetchTms - ErrorMsg: '.$value);

            break;

          // Nothing yet
          case 'ObjectID':

            break;
        }
      }
    }

    // Add thumbnail from artwork
    if (isset($artworkThumbnail))
    {
      $this->addOrUpdateProperty('artworkThumbnail', $artworkThumbnail, $tmsComponent);
    }

    $tmsComponent->save();

    return $tmsComponent->id;
  }

  public function getLastModifiedCheckDate($tmsObjectId)
  {
    // Request object from TMS API
    if (null !== $data = $this->getTmsData('/GetTombstoneDataRest/ObjectID/'.$tmsObjectId))
    {
      $data = $data['GetTombstoneDataRestIdResult'];

      if (isset($data['LastModifiedCheckDate']))
      {
        return $data['LastModifiedCheckDate'];
      }
    }

    return null;
  }

  public function updateArtwork($artwork)
  {
    $artwork->indexOnSave = false;
    list($tmsComponentsIds, $artworkThumbnail) = $this->getTmsObjectData($artwork, $artwork->identifier);

    // Get intermediate level
    $criteria = new Criteria;
    $criteria->add(QubitInformationObject::PARENT_ID, $artwork->id);
    $criteria->add(QubitInformationObject::LEVEL_OF_DESCRIPTION_ID, sfConfig::get('app_drmc_lod_description_id'));
    $components = QubitInformationObject::getOne($criteria);

    $criteria = new Criteria;
    $criteria->add(QubitInformationObject::LFT, $artwork->lft, Criteria::GREATER_THAN);
    $criteria->add(QubitInformationObject::RGT, $artwork->rgt, Criteria::LESS_THAN);
    $criteria->add(QubitInformationObject::LEVEL_OF_DESCRIPTION_ID, $this->componentLevels, Criteria::IN);

    $tmsComponentsIoIds = array();
    foreach (QubitInformationObject::get($criteria) as $component)
    {
      // Update or delete actual components
      if (isset($component->identifier) && false !== $key = array_search($component->identifier, $tmsComponentsIds))
      {
        // Update
        $tmsComponentsIoIds[] = $this->getTmsComponentData($component, $component->identifier, $artworkThumbnail);

        // Remove from array
        unset($tmsComponentsIds[$key]);
      }
      else
      {
        // Move childs to parent of the component
        foreach ($component->getChildren() as $child)
        {
          $child->parentId = $component->parentId;
        }

        // Delete (this also deletes relations with AIPs and other components)
        $component->delete();
      }
    }

    // Create new components with the remaining TMS ids in the array
    foreach ($tmsComponentsIds as $tmsId)
    {
      $tmsComponent = new QubitInformationObject;
      $tmsComponent->parentId = isset($components) ? $components->id : $artwork->id;
      $tmsComponent->levelOfDescriptionId = sfConfig::get('app_drmc_lod_component_id');
      $tmsComponent->setPublicationStatusByName('Published');

      // Update TMS Component data
      $tmsComponentsIoIds[] = $this->getTmsComponentData($tmsComponent, $tmsId, $artworkThumbnail);
    }

    // Save info object components ids as property of the artwork
    // because they are not directly related but added as part of the artwork in ES
    $property = $artwork->getPropertyByName('childComponents');
    $property->setName('childComponents');
    $property->setValue(serialize($tmsComponentsIoIds));
    $property->setObjectId($artwork->id);
    $property->indexOnSave = false;
    $property->save();

    // Update non already updated descendants in ES
    $sql = <<<sql

SELECT
  id
FROM
  information_object
WHERE
  lft > ?
AND
  rgt < ?;

sql;

    $results = QubitPdo::fetchAll($sql, array($artwork->lft, $artwork->rgt));

    foreach ($results as $item)
    {
      if (!in_array($item->id, $tmsComponentsIoIds))
      {
        $node = new arElasticSearchInformationObjectPdo($item->id);
        $data = $node->serialize();

        $this->searchInstance->addDocument($data, 'QubitInformationObject');
      }
    }

    // Update artwork AIPs in ES
    $sql = <<<sql

SELECT
  id
FROM
  aip
WHERE
  part_of = ?;

sql;

    $results = QubitPdo::fetchAll($sql, array($artwork->id));

    foreach ($results as $item)
    {
      $node = new arElasticSearchAipPdo($item->id);
      $data = $node->serialize();

      $this->searchInstance->addDocument($data, 'QubitAip');
    }

    // Add components data for the artwork in ES
    $this->searchInstance->update($artwork);
  }

  protected function addOrUpdateProperty($name, $value, $io, $options = array())
  {
    if (isset($io->id) && null !== $property = QubitProperty::getOneByObjectIdAndName($io->id, $name))
    {
      if (isset($options['scope']))
      {
        $property->scope = $options['scope'];
      }

      $property->value = $value;
      $property->indexOnSave = false;
      $property->save();
    }
    else
    {
      $io->addProperty($name, $value, $options);
    }
  }

  protected function addOrUpdateObjectTermRelation($name, $value, $io)
  {
    $taxonomyId = sfConfig::get('app_drmc_taxonomy_'.strtolower($name).'s_id');
    $term = QubitFlatfileImport::createOrFetchTerm($taxonomyId, $value);

    // Check for existing term relation
    if (isset($io->id))
    {
      $criteria = new Criteria;
      $criteria->add(QubitObjectTermRelation::OBJECT_ID, $io->id);
      $criteria->addJoin(QubitObjectTermRelation::TERM_ID, QubitTerm::ID);
      $criteria->add(QubitTerm::TAXONOMY_ID, $taxonomyId);

      $termRelation = QubitObjectTermRelation::getOne($criteria);
    }

    // Update
    if (isset($termRelation))
    {
      $termRelation->setTermId($term->id);
      $termRelation->indexOnSave = false;
      $termRelation->save();
    }
    // Or create new one
    else
    {
      $termRelation = new QubitObjectTermRelation;
      $termRelation->setTermId($term->id);

      $io->objectTermRelationsRelatedByobjectId[] = $termRelation;
    }
  }

  protected function addOrUpdateNote($typeId, $content, $io)
  {
    // Check for existing note
    if (isset($io->id))
    {
      $criteria = new Criteria;
      $criteria->add(QubitNote::OBJECT_ID, $io->id);
      $criteria->add(QubitNote::TYPE_ID, $typeId);

      $note = QubitNote::getOne($criteria);
    }

    // Update
    if (isset($note))
    {
      $note->content = $content;
      $note->indexOnSave = false;
      $note->save();
    }
    // Or create new one
    else
    {
      $note = new QubitNote;
      $note->content = $content;
      $note->typeId = $typeId;

      $io->notes[] = $note;
    }
  }
}
