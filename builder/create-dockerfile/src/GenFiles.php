<?php
/**
 * Copyright 2017 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Symfony\Component\Yaml\Yaml;

class GenFiles
{
    const APP_DIR = '/app';
    const DEFAULT_BASE_IMAGE = 'gcr.io/google-appengine/php';
    const DEFAULT_TAG = 'latest';
    const DEFAULT_WORKSPACE = '/workspace';

    /* @var string */
    private $workspace;

    /**
     * Constructor allows injecting the workspace directory.
     */
    public function __construct($workspace = self::DEFAULT_WORKSPACE)
    {
        $this->workspace = $workspace;
    }

    private function readAppYaml()
    {
        return Yaml::parse(file_get_contents($this->workspace . '/app.yaml'));
    }

    /**
     * Creates a Dockerfile if it doesn't exist in the workspace.
     */
    public function createDockerfile()
    {
        if (file_exists($this->workspace . '/Dockerfile')) {
            echo 'not creating Dockerfile because the file already exists'
                . PHP_EOL;
            return;
        }
        $docRoot = self::APP_DIR;
        $appYaml = $this->readAppYaml();

        if (array_key_exists('runtime_config', $appYaml)
            && array_key_exists('document_root', $appYaml['runtime_config'])) {
            $docRoot = self::APP_DIR . '/'
                . $appYaml['runtime_config']['document_root'];
        }
        $tag = getenv('BUILDER_TARGET_TAG');
        if ($tag === false) {
            $tag = self::DEFAULT_TAG;
        }
        $baseImage = getenv('BUILDER_TARGET_IMAGE');
        if ($baseImage === false) {
            $baseImage = self::DEFAULT_BASE_IMAGE;
        }
        $loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
        $twig = new Twig_Environment($loader);
        $template = $twig->load('Dockerfile.twig');
        $dockerfile = $template->render(array(
            'base_image' => $baseImage,
            'tag' => $tag,
            'document_root' => $docRoot
        ));
        file_put_contents($this->workspace . '/Dockerfile', $dockerfile);
    }

    /**
     * Creates a .dockerignore if it doesn't exist in the workspace.
     */
    public function createDockerignore()
    {
        if (file_exists($this->workspace . '/.dockerignore')) {
            echo 'not creating .dockerignore because the file already exists'
                . PHP_EOL;
            return;
        }
        copy(
            __DIR__ . '/templates/dockerignore.tmpl',
            $this->workspace . '/.dockerignore'
        );
    }
}
