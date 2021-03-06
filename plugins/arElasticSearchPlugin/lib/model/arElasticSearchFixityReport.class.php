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

class arElasticSearchFixityReport extends arElasticSearchModelBase
{
  public function populate()
  {
    $sql  = 'SELECT id';
    $sql .= ' FROM '.QubitFixityReport::TABLE_NAME;

    $reports = QubitPdo::fetchAll($sql, array('QubitFixityReport'));

    $this->count = count($reports);

    // Loop through results, and add to search index
    foreach ($reports as $key => $item)
    {
      $node = new arElasticSearchFixityReportPdo($item->id);
      $data = $node->serialize();

      QubitSearch::getInstance()->addDocument($data, 'QubitFixityReport');

      $this->logEntry($data['timeStarted'], $key + 1);
    }
  }

  public static function update($object)
  {
    $node = new arElasticSearchFixityReportPdo($object->id);

    QubitSearch::getInstance()->addDocument($node->serialize(), 'QubitFixityReport');

    return true;
  }
}
