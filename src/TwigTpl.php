<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace fkooman\RemoteStorage;

use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigTpl implements TplInterface
{
    private Environment $twig;

    /** @var array */
    private $defaultVariables;

    /**
     * Create TwigTemplateManager.
     *
     * @param array  $templateDirs template directories to look in where later
     *                             paths override the earlier paths
     * @param string $cacheDir     the writable directory to store the cache
     */
    public function __construct(array $templateDirs, $cacheDir = null)
    {
        $existingTemplateDirs = [];
        foreach ($templateDirs as $templateDir) {
            if (false !== is_dir($templateDir)) {
                $existingTemplateDirs[] = $templateDir;
            }
        }
        $existingTemplateDirs = array_reverse($existingTemplateDirs);

        $environmentOptions = [
            'strict_variables' => true,
        ];

        if (null !== $cacheDir) {
            if (false === is_dir($cacheDir)) {
                if (false === @mkdir($cacheDir, 0700, true)) {
                    throw new RuntimeException('unable to create template cache directory');
                }
            }
            $environmentOptions['cache'] = $cacheDir;
        }

        $this->twig = new Environment(
            new FilesystemLoader(
                $existingTemplateDirs
            ),
            $environmentOptions
        );

        $this->defaultVariables = [];
    }

    public function setDefault(array $templateVariables)
    {
        $this->defaultVariables = $templateVariables;
    }

    public function addDefault(array $templateVariables)
    {
        $this->defaultVariables = array_merge(
            $this->defaultVariables, $templateVariables
        );
    }

    /**
     * Render the template.
     */
    public function render(string $templateName, array $templateVariables): string
    {
        $templateVariables = array_merge($this->defaultVariables, $templateVariables);

        return $this->twig->render(
            sprintf(
                '%s.twig',
                $templateName
            ),
            $templateVariables
        );
    }
}
