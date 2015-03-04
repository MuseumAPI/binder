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
 * arRestApiPluginConfiguration configuration.
 *
 * @package     arRestApiPluginConfiguration
 * @subpackage  config
 */
class arRestApiPluginConfiguration extends sfPluginConfiguration
{
  const REGEX_UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
  const REGEX_ID   = '\d+';
  const REGEX_SLUG = '[0-9A-Za-z-]+';

  public function routingLoadConfiguration(sfEvent $event)
  {
    $this->routing = $event->getSubject();

    // How slow is inserting the routes here? I don't think I can obtain the
    // same results using a nested routing.yml files in arRestApiPlugin because
    // there's no way to bypass some of the catch-any routes in the main yaml.
    // This is probably not being cached at all :(

    /**
     * Dashboard resources
     */

    $this->addRoute('GET', '/api/dashboard', array(
      'module' => 'api',
      'action' => 'dashboardView'));

    /**
     * AIP resources
     */

    $this->addRoute('GET', '/api/aips', array(
      'module' => 'api',
      'action' => 'aipsBrowse'));

    $this->addRoute('GET', '/api/aips/:uuid', array(
      'module' => 'api',
      'action' => 'aipsView',
      'params' => array('uuid' => self::REGEX_UUID)));

    $this->addRoute('GET', '/api/aips/:uuid/files', array(
      'module' => 'api',
      'action' => 'aipsFiles',
      'params' => array('id' => self::REGEX_UUID)));

    $this->addRoute('GET', '/api/aips/:uuid/download', array(
      'module' => 'api',
      'action' => 'aipsDownloadView',
      'params' => array('uuid' => self::REGEX_UUID)));

    $this->addRoute('POST', '/api/aips/:uuid/reclassify', array(
      'module' => 'api',
      'action' => 'aipsReclassify',
      'params' => array('uuid' => self::REGEX_UUID)));

    /**
     * Information object resources
     */

    $this->addRoute('GET', '/api/informationobjects', array(
      'module' => 'api',
      'action' => 'informationobjectsBrowse'));

    $this->addRoute('POST', '/api/informationobjects', array(
      'module' => 'api',
      'action' => 'informationobjectsCreate'));

    $this->addRoute('GET', '/api/informationobjects/:id', array(
      'module' => 'api',
      'action' => 'informationobjectsRead',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('PUT', '/api/informationobjects/:id', array(
      'module' => 'api',
      'action' => 'informationobjectsUpdate',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('DELETE', '/api/informationobjects/:id', array(
      'module' => 'api',
      'action' => 'informationobjectsDelete',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('POST', '/api/informationobjects/:id/move', array(
      'module' => 'api',
      'action' => 'informationobjectsMove',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/:id/tree', array(
      'module' => 'api',
      'action' => 'informationobjectsTree',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/:id/tree/associations', array(
      'module' => 'api',
      'action' => 'informationobjectsTreeAssociations',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/:id/files', array(
      'module' => 'api',
      'action' => 'informationobjectsFiles',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/:id/mets', array(
      'module' => 'api',
      'action' => 'informationobjectsMets',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/:id/tms', array(
      'module' => 'api',
      'action' => 'informationobjectsTms',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/:id/aips', array(
      'module' => 'api',
      'action' => 'informationobjectsAips',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/association/:id', array(
      'module' => 'api',
      'action' => 'informationobjectsReadAssociation',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('DELETE', '/api/informationobjects/association/:id', array(
      'module' => 'api',
      'action' => 'informationobjectsDeleteAssociation',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('PUT', '/api/informationobjects/association/:id', array(
      'module' => 'api',
      'action' => 'informationobjectsUpdateAssociation',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('POST', '/api/informationobjects/:id/associate', array(
      'module' => 'api',
      'action' => 'informationobjectsAssociate',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/:id/technologies', array(
      'module' => 'api',
      'action' => 'informationobjectsRelatedTechnologiesRead',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('POST', '/api/informationobjects/:id/technologies', array(
      'module' => 'api',
      'action' => 'informationobjectsRelatedTechnologiesCreate',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/informationobjects/works', array(
      'module' => 'api',
      'action' => 'informationobjectsWorksBrowse'));

    $this->addRoute('GET', '/api/informationobjects/components', array(
      'module' => 'api',
      'action' => 'informationobjectsComponentsBrowse'));

    $this->addRoute('GET', '/api/informationobjects/technologies', array(
      'module' => 'api',
      'action' => 'informationobjectsTechnologyRecordsBrowse'));

    $this->addRoute('GET', '/api/informationobjects/files', array(
      'module' => 'api',
      'action' => 'informationobjectsFilesBrowse'));

    $this->addRoute('GET', '/api/informationobjects/:id/status', array(
      'module' => 'api',
      'action' => 'informationobjectsWorksStatus',
      'params' => array('id' => self::REGEX_ID)));

    /**
     * Actors
     */

    $this->addRoute('GET', '/api/actors', array(
      'module' => 'api',
      'action' => 'actorsBrowse'));

    $this->addRoute('GET', '/api/actors/:id', array(
      'module' => 'api',
      'action' => 'actorsDetail'));

    $this->addRoute('POST', '/api/actors', array(
      'module' => 'api',
      'action' => 'actorsCreate'));

    $this->addRoute('PUT', '/api/actors/:id', array(
      'module' => 'api',
      'action' => 'actorsUpdate',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('DELETE', '/api/actors/:id', array(
      'module' => 'api',
      'action' => 'actorsDelete',
      'params' => array('id' => self::REGEX_ID)));

    /**
     * Taxonomies and terms
     */

    $this->addRoute('GET', '/api/taxonomies/:id', array(
      'module' => 'api',
      'action' => 'taxonomiesBrowse',
      'params' => array('id' => self::REGEX_ID)));

    /**
     * Fixed value lists
     */

    $this->addRoute('GET', '/api/countries', array(
      'module' => 'api',
      'action' => 'countriesBrowse'));

    /**
     * Recent activity
     */

    $this->addRoute('GET', '/api/activity/downloads', array(
      'module' => 'api',
      'action' => 'activityDownloads'));

    $this->addRoute('GET', '/api/activity/ingestion', array(
      'module' => 'api',
      'action' => 'activityIngestion'));

    /**
     * Summaries
     */

    $this->addRoute('GET', '/api/summary/ingestion', array(
      'module' => 'api',
      'action' => 'summaryIngestion'));

    $this->addRoute('GET', '/api/summary/artworkbydate', array(
      'module' => 'api',
      'action' => 'summaryArtworkByDate'));

    $this->addRoute('GET', '/api/summary/storagebymediacategory', array(
      'module' => 'api',
      'action' => 'summaryStorageUsedByMediaCategory'));

    $this->addRoute('GET', '/api/summary/storagebycodec', array(
      'module' => 'api',
      'action' => 'summaryStorageUsedByCodec'));

    $this->addRoute('GET', '/api/summary/departmentartworkcount', array(
      'module' => 'api',
      'action' => 'summaryDepartmentArtworkCount'));

    $this->addRoute('GET', '/api/summary/mediafilesizebycollectionyear', array(
      'module' => 'api',
      'action' => 'summaryMediaFilesizeByYear'));

    /**
     * Search
     */

    $this->addRoute('GET', '/api/search/autocomplete', array(
      'module' => 'api',
      'action' => 'searchAutocomplete'));

    $this->addRoute('GET', '/api/searches', array(
      'module' => 'api',
      'action' => 'searchesBrowse'));

    $this->addRoute('POST', '/api/searches', array(
      'module' => 'api',
      'action' => 'searchesCreate'));

    $this->addRoute('GET', '/api/searches/:input', array(
      'module' => 'api',
      'action' => 'searchesRead',
      // TODO: Should we have two routes instead?
      'params' => array('input' => self::REGEX_ID . '|' . self::REGEX_SLUG)));

    $this->addRoute('PUT', '/api/searches/:id', array(
      'module' => 'api',
      'action' => 'searchesUpdate',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('DELETE', '/api/searches/:id', array(
      'module' => 'api',
      'action' => 'searchesDelete',
      'params' => array('id' => self::REGEX_ID)));


    /**
     * Users and authentication
     */

    $this->addRoute('GET,POST,DELETE', '/api/users/authenticate', array(
      'module' => 'api',
      'action' => 'usersAuthenticate'));

    /**
     * Fixity reports
     */

    $this->addRoute('POST', '/api/fixity/:uuid', array(
      'module' => 'api',
      'action' => 'fixityCreate',
      'params' => array('uuid' => self::REGEX_UUID)));

    $this->addRoute('GET', '/api/fixity/:uuid', array(
      'module' => 'api',
      'action' => 'fixityBrowse',
      'params' => array('uuid' => self::REGEX_UUID)));

    $this->addRoute('GET', '/api/fixity/status', array(
      'module' => 'api',
      'action' => 'fixityStatus'));

    /**
     * AIP recovery
     */

    $this->addRoute('POST', '/api/recover/:id', array(
      'module' => 'api',
      'action' => 'recoverRequest',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('POST', '/api/recover/results', array(
      'module' => 'api',
      'action' => 'recoverResults'));

    /**
     * reports
     */

    // '/api/reports' doesn't work, using report instead for the moment
    $this->addRoute('GET', '/api/report', array(
      'module' => 'api',
      'action' => 'reportsGenerate'));

    $this->addRoute('GET', '/api/reportcsv', array(
      'module' => 'api',
      'action' => 'reportsGenerateCsv'));

    $this->addRoute('POST', '/api/report', array(
      'module' => 'api',
      'action' => 'reportsCreate'));

    $this->addRoute('GET', '/api/reports/browse', array(
      'module' => 'api',
      'action' => 'reportsBrowse'));

    $this->addRoute('DELETE', '/api/reports/:id', array(
      'module' => 'api',
      'action' => 'reportsDelete',
      'params' => array('id' => self::REGEX_ID)));

    $this->addRoute('GET', '/api/reports/:input', array(
      'module' => 'api',
      'action' => 'reportsRead',
      // TODO: Should we have two routes instead?
      'params' => array('input' => self::REGEX_ID . '|' . self::REGEX_SLUG)));

  }

  protected function addRoute($method, $pattern, array $options = array())
  {
    $defaults = $requirements = array();

    $requirements['sf_method'] = explode(',', $method);

    if (isset($options['module']))
    {
      $defaults['module'] = $options['module'];
    }

    if (isset($options['action']))
    {
      $defaults['action'] = $options['action'];
      $name = 'api_'.$options['action'];
    }
    else
    {
      $name = 'api_'.str_replace('/', '_', $pattern);
    }

    if (isset($options['params']))
    {
      $params = $options['params'];
      foreach ($params as $field => $regex)
      {
        $requirements[$field] = $regex;
      }
    }

    // Add route before slug;default_index
    $this->routing->insertRouteBefore('slug;default_index', $name,
      new sfRequestRoute($pattern, $defaults, $requirements));
  }

  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    // Enable sfInstallPlugin module
    $enabledModules = sfConfig::get('sf_enabled_modules');
    $enabledModules[] = 'api';
    sfConfig::set('sf_enabled_modules', $enabledModules);

    // Connect event listener to add routes
    $this->dispatcher->connect('routing.load_configuration', array($this, 'routingLoadConfiguration'));
  }
}
