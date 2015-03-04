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
 * Represent the time, place and/or agent of events in an artifact's history
 *
 * @package    AccesstoMemory
 * @subpackage model
 */
class QubitAip extends BaseAip
{
  /**
   * Additional save functionality (e.g. update search index)
   *
   * @param mixed $connection a database connection object
   * @return QubitAip self-reference
   */
  public function save($connection = null)
  {
    parent::save($connection);

    QubitSearch::getInstance()->update($this);

    // Update part_of artwork in ES
    if (isset($this->partOf) && null !== $partOf = QubitInformationObject::getById($this->partOf))
    {
      QubitSearch::getInstance()->update($partOf);
    }

    // TODO: Update attached_to and childs

    return $this;
  }

  /**
   * Additional actions to take on delete
   *
   */
  public function delete($connection = null)
  {
    // Physical object relations
    $relations = QubitRelation::getRelationsBySubjectId($this->id, array('typeId' => QubitTerm::AIP_RELATION_ID));
    foreach ($relations as $item)
    {
      $item->indexObjectOnDelete = false;
      $item->delete();
    }

    QubitSearch::getInstance()->delete($this);

    parent::delete($connection);
  }

  public static function getByUuid($uuid)
  {
    $criteria = new Criteria;
    $criteria->add(QubitAIP::UUID, $uuid);

    return QubitAip::getOne($criteria);
  }
}
