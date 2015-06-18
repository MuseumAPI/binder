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

class ApiInformationObjectsTreeAction extends QubitApiAction
{
  protected function get($request)
  {
    $data = $this->getTree();

    return $data;
  }

  protected function getTree()
  {
    $sql = <<<EOL
SELECT
  a.*,
  property_i18n.value as component_number,
  CASE WHEN b.hits IS NULL THEN 0 ELSE 1 END supporting_technologies_count
FROM
  (SELECT
     node.id,
     node.level_of_description_id,
     i18n.title,
     node.lft, node.rgt, node.parent_id
   FROM
     information_object AS node,
     information_object AS parent,
     information_object_i18n AS i18n
   WHERE
     node.lft BETWEEN parent.lft AND parent.rgt
     AND node.id = i18n.id
     AND node.source_culture = i18n.culture
     AND parent.id = ?
   ORDER BY node.lft) a
LEFT JOIN
  (SELECT
     subject_id, COUNT(*) hits
   FROM relation, term
   WHERE
     relation.type_id = term.id
     AND term.taxonomy_id = ?
   GROUP BY subject_id
  ) b ON (a.id = b.subject_id)
LEFT JOIN property
  ON a.id = property.object_id
  AND property.name = 'ComponentNumber'
LEFT JOIN property_i18n
  ON property.id = property_i18n.id
  AND property.source_culture = property_i18n.culture
EOL;

    $results = QubitPdo::fetchAll($sql, array(
      $this->request->id,
      sfConfig::get('app_drmc_taxonomy_supporting_technologies_relation_types_id')));
    if (0 === count($results))
    {
      throw new QubitApi404Exception('Information object not found');
    }
    else if (false === $results)
    {
      throw new QubitApiException;
    }

    // Build a nested set of objects.
    // Notice that fetchAll returns objects, not arrays.
    $data = new stdClass; // Here is where we are storing the nested set
    $flat = array();      // Flat hashmap (id => ref-to-obj) for quick searches
    $add = function (&$target, $item)
    {
      if ($item->level_of_description_id == sfConfig::get('app_drmc_lod_digital_object_id'))
      {
        return;
      }

      // TODO: don't reuse PDO item.

      // Cleanups of data coming from MySQL/PDO
      $item->id = (int)$item->id;
      $item->level_of_description_id = (int)$item->level_of_description_id;
      $item->supporting_technologies_count = (int)$item->supporting_technologies_count;
      unset($item->lft);
      unset($item->rgt);
      unset($item->parent_id);

      if (empty($item->title))
      {
        $item->title = 'Untitled';
      }

      if (isset($item->component_number))
      {
        $item->title = $item->component_number . ' - ' . $item->title;
      }

      unset($item->component_number);

      if (!isset($target) || is_array($target))
      {
        $target[] = $item;
      }
      else
      {
        $target = $item;
      }
    };

    foreach ($results as $item)
    {
      if (isset($flat[$item->parent_id]))
      {
        $parent = $flat[$item->parent_id];
        $add($parent->children, $item);
      }
      else
      {
        $add($data, $item);
      }

      $flat[$item->id] = $item;
    }

    return $data;
  }
}
