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

class ApiSearchesReadAction extends QubitApiAction
{
  protected function get($request)
  {
    if (ctype_digit($request->input))
    {
      try
      {
        $result = QubitSearch::getInstance()->index->getType('QubitSavedQuery')->getDocument($request->input);
      }
      catch (\Elastica\Exception\NotFoundException $e)
      {
        throw new QubitApi404Exception('Search not found');
      }
    }
    else
    {
      $query = new \Elastica\Query;
      $queryBool = new \Elastica\Query\BoolQuery;

      $queryText = new \Elastica\Query\QueryString($request->input);
      $queryText->setFields(array('slug'));

      $queryBool->addMust($queryText);
      $queryBool->addMust(new \Elastica\Query\Term(array('typeId' => sfConfig::get('app_drmc_term_search_id'))));

      $query->setQuery($queryBool);

      $resultSet = QubitSearch::getInstance()->index->getType('QubitSavedQuery')->search($query);

      if ($resultSet->getTotalHits() < 1)
      {
        throw new QubitApi404Exception('Search not found');
      }

      $result = $resultSet->getResults();
      $result = $result[0];
    }

    $doc = $result->getData();
    $search = array();

    $this->addItemToArray($search, 'id', $result->getId());
    $this->addItemToArray($search, 'name', $doc['name']);
    $this->addItemToArray($search, 'type', $doc['scope']);
    $this->addItemToArray($search, 'description', $doc['description']);
    $this->addItemToArray($search, 'criteria', unserialize($doc['params']));

    return $search;
  }
}
