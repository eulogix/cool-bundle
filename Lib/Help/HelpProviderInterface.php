<?php

/*
 * This file is part of the Eulogix\Cool package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Cool\Lib\Help;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

interface HelpProviderInterface {

    /**
     * @param array $parameters
     * @return array
     */
    public function getHelp(array $parameters);

}