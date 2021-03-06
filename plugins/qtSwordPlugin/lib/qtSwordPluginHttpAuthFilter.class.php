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

class qtSwordPluginHttpAuthFilter extends sfFilter
{
  public function execute($filterChain)
  {
    if ($this->isFirstCall())
    {
      $context = $this->getContext();

      // If the user have been already authenticated (e.g. via cookies/session),
      // we can just ignore the Authorization header and let it pass.
      if ($context->getUser()->isAuthenticated())
      {
        $filterChain->execute();

        return;
      }

      if (!isset($_SERVER['PHP_AUTH_USER']))
      {
        $this->sendHeaders();

        exit;
      }

      $user = QubitUser::checkCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $error);

      if (null === $user)
      {
        $this->sendHeaders();

        return;
      }

      $user = new myUser(new sfEventDispatcher(), new sfNoStorage());
      $user->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

      // We'll need username/email details later
      sfContext::getInstance()->request->setAttribute('user', $user);
    }

    $filterChain->execute();
  }

  private function sendHeaders()
  {
    header('WWW-Authenticate: Basic realm="Secure area"');
    header('HTTP/1.0 401 Unauthorized');
  }
}
