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

class ApiActivityDownloadsAction extends QubitApiAction
{
  protected function get($request)
  {
    $data = array();

    $data['results'] = $this->getResults();

    return $data;
  }

  protected function getResults()
  {
    $results = array();
    $limit = ($this->request->limit) ? $this->request->limit : 10;

    // pull download log data in reverse chronological order
    $criteria = new Criteria;
    $criteria->add(QubitAccessLog::TYPE_ID, QubitTerm::ACCESS_LOG_AIP_DOWNLOAD_ENTRY);
    $criteria->addOr(QubitAccessLog::TYPE_ID, QubitTerm::ACCESS_LOG_AIP_FILE_DOWNLOAD_ENTRY);
    $criteria->addDescendingOrderByColumn(QubitAccessLog::DATE);

    $criteria->setLimit($limit);

    $entries = QubitAccessLog::get($criteria);

    foreach ($entries as $entry)
    {
      $downloadType = ($entry->typeId == QubitTerm::ACCESS_LOG_AIP_FILE_DOWNLOAD_ENTRY) ? 'File' : 'AIP';

      if ($entry->typeId == QubitTerm::ACCESS_LOG_AIP_FILE_DOWNLOAD_ENTRY)
      {
        $file = QubitInformationObject::getById($entry->objectId);
        $downloadFile = $file->title;
        $downloadUUID = null;
      }
      else
      {
        $aip = QubitAip::getById($entry->objectId);
        $downloadFile = $aip->filename;
        $downloadUUID = $aip->uuid;
      }

      $download = array(
        'objectId' => $entry->objectId,
        'date' => $entry->date,
        'username' => $entry->user->getUsername(),
        'reason' => $entry->reason,
        'file' => $downloadFile,
        'type' => $downloadType
      );

      if (!is_null($downloadUUID))
      {
        $download['uuid'] = $downloadUUID;
      }

      $results[] = $download;
    }

    return $results;
  }
}
