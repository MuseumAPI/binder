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

class ApiAipsBrowseAction extends QubitApiAction
{
  protected function get($request)
  {
    $data = array();

    $results = $this->getResults();
    $data['results'] = $results['results'];
    $data['facets'] = $results['facets'];
    $data['total'] = $results['total'];
    $data['overview'] = $this->getOverview();

    return $data;
  }

  protected function getResults()
  {
    // Create query objects
    $query = new \Elastica\Query;
    $queryBool = new \Elastica\Query\Bool;
    $filterBool = new \Elastica\Filter\Bool;
    $queryBool->addMust(new \Elastica\Query\MatchAll);

    // Pagination and sorting
    $this->prepareEsPagination($query);
    $this->prepareEsSorting($query, array(
      'name' => 'filename',
      'size' => 'sizeOnDisk',
      'createdAt' => 'createdAt'));
      // TODO
      // 'typeId' => '',
      // 'partOf' => ''));

    // Filter selected facets
    $this->filterEsFacetQuery('type', 'type.id', $queryBool);

    $this->filterEsRangeFacet('sizeFrom', 'sizeTo', 'sizeOnDisk', $queryBool);
    $this->filterEsRangeFacet('ingestedFrom', 'ingestedTo', 'createdAt', $queryBool);

    $this->filterEsFacetQuery('format', 'digitalObjects.metsData.mediainfo.generalTracks.format', $queryBool, 'AND', array('noInteger' => true));
    $this->filterEsFacetQuery('videoCodec', 'digitalObjects.metsData.mediainfo.videoTracks.codec', $queryBool, 'AND', array('noInteger' => true));
    $this->filterEsFacetQuery('audioCodec', 'digitalObjects.metsData.mediainfo.audioTracks.codec', $queryBool, 'AND', array('noInteger' => true));
    $this->filterEsFacetQuery('resolution', 'digitalObjects.metsData.mediainfo.videoTracks.resolution', $queryBool);
    $this->filterEsFacetQuery('chromaSubSampling', 'digitalObjects.metsData.mediainfo.videoTracks.chromaSubsampling', $queryBool, 'AND', array('noInteger' => true));
    $this->filterEsFacetQuery('colorSpace', 'digitalObjects.metsData.mediainfo.videoTracks.colorSpace', $queryBool, 'AND', array('noInteger' => true));
    $this->filterEsFacetQuery('sampleRate', 'digitalObjects.metsData.mediainfo.audioTracks.samplingRate', $queryBool);
    $this->filterEsFacetQuery('bitDepth', 'digitalObjects.metsData.mediainfo.videoTracks.bitDepth', $queryBool);

    // Add facets to the query
    $this->facetEsQuery('Terms', 'format', 'digitalObjects.metsData.mediainfo.generalTracks.format', $query);
    $this->facetEsQuery('Terms', 'videoCodec', 'digitalObjects.metsData.mediainfo.videoTracks.codec', $query);
    $this->facetEsQuery('Terms', 'audioCodec', 'digitalObjects.metsData.mediainfo.audioTracks.codec', $query);
    $this->facetEsQuery('Terms', 'resolution', 'digitalObjects.metsData.mediainfo.videoTracks.resolution', $query);
    $this->facetEsQuery('Terms', 'chromaSubSampling', 'digitalObjects.metsData.mediainfo.videoTracks.chromaSubsampling', $query);
    $this->facetEsQuery('Terms', 'colorSpace', 'digitalObjects.metsData.mediainfo.videoTracks.colorSpace', $query);
    $this->facetEsQuery('Terms', 'sampleRate', 'digitalObjects.metsData.mediainfo.audioTracks.samplingRate', $query);
    $this->facetEsQuery('Terms', 'bitDepth', 'digitalObjects.metsData.mediainfo.videoTracks.bitDepth', $query);

    $this->facetEsQuery('Terms', 'type', 'type.id', $query);

    $sizeRanges = array(
      array('to' => 512000),
      array('from' => 512000, 'to' => 1048576),
      array('from' => 1048576, 'to' => 2097152),
      array('from' => 2097152, 'to' => 5242880),
      array('from' => 5242880, 'to' => 10485760),
      array('from' => 10485760));

    $this->facetEsQuery('Range', 'size', 'sizeOnDisk', $query, array('ranges' => $sizeRanges));

    $now = new DateTime();
    $now->setTime(0, 0);

    $dateRanges = array(
      array('to' => $now->modify('-1 year')->getTimestamp().'000'),
      array('from' => $now->getTimestamp().'000'),
      array('from' => $now->modify('+11 months')->getTimestamp().'000'),
      array('from' => $now->modify('+1 month')->modify('-7 days')->getTimestamp().'000'));

    $this->dateRangesLabels = array(
      'Older than a year',
      'From last year',
      'From last month',
      'From last week');

    $this->facetEsQuery('Range', 'dateIngested', 'createdAt', $query, array('ranges' => $dateRanges));

    // Filter query
    if (isset($this->request->query) && 1 !== preg_match('/^[\s\t\r\n]*$/', $this->request->query))
    {
      $culture = sfContext::getInstance()->user->getCulture();

      $queryFields = array(
        'filename.autocomplete',
        'uuid',
        'partOf.i18n.'.$culture.'.title'
      );

      $queryText = new \Elastica\Query\QueryString($this->request->query);
      $queryText->setFields($queryFields);

      $queryBool->addMust($queryText);
    }

    // Assign query
    $query->setQuery($queryBool);

    // Set filter
    if (0 < count($filterBool->toArray()))
    {
      $query->setFilter($filterBool);
    }

    $resultSet = QubitSearch::getInstance()->index->getType('QubitAip')->search($query);

    $data = array();
    foreach ($resultSet as $hit)
    {
      $doc = $hit->getData();

      $aip = array();

      $this->addItemToArray($aip, 'id', $hit->getId());
      $this->addItemToArray($aip, 'name', $doc['filename']);
      $this->addItemToArray($aip, 'uuid', $doc['uuid']);
      $this->addItemToArray($aip, 'size', $doc['sizeOnDisk']);
      $this->addItemToArray($aip, 'created_at', arRestApiPluginUtils::convertDate($doc['createdAt']));

      if (isset($doc['type']))
      {
        $this->addItemToArray($aip['type'], 'id', $doc['type']['id']);
        $this->addItemToArray($aip['type'], 'name', get_search_i18n($doc['type'], 'name'));
      }

      if (isset($doc['partOf']))
      {
        $this->addItemToArray($aip['part_of'], 'id', $doc['partOf']['id']);
        $this->addItemToArray($aip['part_of'], 'title', get_search_i18n($doc['partOf'], 'title'));
        $this->addItemToArray($aip['part_of'], 'level_of_description_id', $doc['partOf']['levelOfDescriptionId']);
      }

      $this->addItemToArray($aip, 'digital_object_count', $doc['digitalObjectCount']);

      $data['results'][] = $aip;
    }

    // Facets
    $facets = $resultSet->getFacets();
    $this->populateFacets($facets);
    $data['facets'] = $facets;

    // Total this
    $data['total'] = $resultSet->getTotalHits();

    return $data;
  }

  protected function getOverview()
  {
    // Create query objects
    $query = new \Elastica\Query;
    $queryBool = new \Elastica\Query\Bool;
    $queryBool->addMust(new \Elastica\Query\MatchAll);

    // Add facets to the query
    $this->facetEsQuery('TermsStats', 'type', 'type.id', $query, array(
      'valueField' => 'sizeOnDisk'));

    // ES only gets 10 results if size is not set and we need all the hits
    // to calculate the unclassified counts. This can be fixed adding an
    // unclassified type value to the ES index to make the facet work with it
    // or using the missing aggregation (ES 1.3)
    // Max int32 value may cause OutOfMemoryError
    $query->setSize(99999);

    // Assign query
    $query->setQuery($queryBool);

    $resultSet = QubitSearch::getInstance()->index->getType('QubitAip')->search($query);
    $facets = $resultSet->getFacets();

    $results = array();
    $totalSize = $totalCount = 0;
    foreach ($facets['type']['terms'] as $facet)
    {
      $results[$facet['term']] = array(
        'size' => $facet['total'],
        'count' => $facet['count']);

      $totalSize += $facet['total'];
      $totalCount += $facet['count'];
    }

    // Get unclassified counts (missing type.id)
    $results['unclassified']['count'] = $results['unclassified']['size'] = 0;
    foreach ($resultSet as $hit)
    {
      $doc = $hit->getData();

      if (isset($doc['type']['id']))
      {
        continue;
      }

      $results['unclassified']['size'] += $doc['sizeOnDisk'];
      $results['unclassified']['count'] ++;
    }

    $results['total'] = array(
      'size' => $totalSize + $results['unclassified']['size'],
      'count' => $totalCount + $results['unclassified']['count']);

    return $results;
  }

  protected function getFacetLabel($name, $id)
  {
    switch ($name)
    {
      case 'type':
        if (null !== $item = QubitTerm::getById($id))
        {
          return $item->getName(array('cultureFallback' => true));
        }

        break;

      case 'dateIngested':
        return $this->dateRangesLabels[$id];

        break;

      case 'format':
      case 'videoCodec':
      case 'audioCodec':
      case 'chromaSubSampling':
      case 'colorSpace':
        return $id;

        break;

      case 'resolution':
      case 'bitDepth':
        return $id.' bits';

        break;

      case 'sampleRate':
        return $id.' Hz';

        break;
    }
  }
}
