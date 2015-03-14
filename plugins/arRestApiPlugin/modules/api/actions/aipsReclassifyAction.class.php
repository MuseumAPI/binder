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

class ApiAipsReclassifyAction extends QubitApiAction
{
  protected function post($request, $payload)
  {
    if (null === $aip = QubitAip::getByUuid($request->uuid))
    {
      throw new QubitApi404Exception('UUID not found');
    }

    if (!property_exists($payload, 'type_id'))
    {
      throw new QubitApiException('Missing parameter type_id', 500);
    }

    if (null !== $payload->type_id && is_int($payload->type_id))
    {
      if (null === $term = QubitTerm::getById($payload->type_id))
      {
        throw new QubitApi404Exception('Term not found');
      }

      if ($term->taxonomyId != QubitTaxonomy::AIP_TYPE_ID)
      {
        throw new QubitApiException('Term not recognized', 500);
      }

      $aip->typeId = $term->id;
    }
    else
    {
      $aip->typeId = NULL;
    }

    try
    {
      $aip->save();
    }
    catch (Exception $e)
    {
      $this->forwardError();
    }

    return array(
      'status' => 'Saved');
  }
}
