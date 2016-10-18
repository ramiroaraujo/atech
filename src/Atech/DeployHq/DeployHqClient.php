<?php
/**
 * Copyright 2012 Barry Carlyon. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Atech\DeployHq;

use Atech\Common\Client\AbstractClient;

/**
 * Client to interact with CodeBase
 *
 */
class DeployHqClient extends AbstractClient
{
    static $dataType = 'application/json';
    /**
    * Spawn
    *
    * @param string $domain  SubDomain of the deployhq account
    * @param string $apiuser API Username normally a email address
    * @param string $apikey  API Key
    *
    * @return class object
    */
    public function __construct($domain, $apiuser, $apikey)
    {
        return parent::build(DeployHqClient::$dataType, 'https://' . $domain . '.deployhq.com/', $apiuser, $apikey);
    }

    /**
    Projects
    */

    /**
    * Get All Projects
    *
    * @return array of arrays describing a project
    */
    public function projects()
    {
        return $this->get('projects');
    }

    /**
    * Get a specific project
    *
    * @param string $permalink permalink of project to return
    *
    * @return array describing a project
    */
    public function project($permalink)
    {
        return $this->get('projects/' . $permalink);
    }

    /**
     * Create a new project
     * @param $name
     * @return string
     */
    public function createProject($name)
    {
        $payload = array(
            'project' => array(
                'name' => $name
            )
        );
        $payload = json_encode($payload);
        return $this->post('projects', $payload);
    }

    public function deleteProject($permalink)
    {
        return $this->delete('projects/' . $permalink);
    }

    /**
    * Get current project reivsion from the repository
    *
    * @param string $permalink permalink of project to return
    * @param string $branch    pass a branch name to get latest revision for branch
    *
    * @return string latest revision key
    */
    public function projectLatest($permalink, $branch = '')
    {
        if ($branch) {
            $branch = '?branch=' . $branch;
        }
        return $this->get('projects/' . $permalink . '/repository/latest_revision' . $branch);
    }

    /**
     * create a repository for the project
     * @param $permalink
     * @param $type
     * @param $url
     * @param string $default_branch
     * @return string
     */
    public function addRepository($permalink, $type, $url, $default_branch = 'master')
    {
        $payload = array(
            'repository' => array(
                'scm_type' => $type,
                'url'      => $url,
                'branch'   => $default_branch,
            )
        );
        $payload = json_encode($payload);
        return $this->post("projects/$permalink/repository", $payload);
    }

    /**
    Servers
    */

    /**
    * Get servers for a project
    *
    * @param string $permalink permalink of project to return
    *
    * @return array an array of arrays describing the servers for the project
    */
    public function servers($permalink)
    {
        return $this->get('projects/' . $permalink . '/servers');
    }

    /**
     * Add a new server to a project
     * @param $permalink
     * @param $data
     * @return string
     */
    public function addServer($permalink, $data)
    {
        $payload = array(
            'server' => $data
        );
        $payload = json_encode($payload);
        return $this->post("projects/$permalink/servers", $payload);
    }

    /**
     * Add an SSH command as a hook for deployments
     * @param $permalink
     * @param $description
     * @param $command
     * @param string $when
     * @param string $timing
     * @param string $servers
     * @param bool $halt_on_error
     * @return \Atech\Common\Client\the
     */
    public function addCommand($permalink, $description, $command, $when = 'after_changes', $timing = 'all', $servers = 'all', $halt_on_error = false)
    {
        $payload = array(
            'command' => [
                'description'        => $description,
                'command'            => $command,
                'cback'              => $when,
                'timing'             => $timing,
                'halt_on_error'      => $halt_on_error,
                'server_identifiers' => $servers,
            ]
        );
        if (is_string($servers) && $servers === 'all') {
            $payload['command']['all_servers'] = true;
        } else {
            //@todo fetch servers and match against domains in array
        }

        $payload = json_encode($payload);
        return $this->post("projects/$permalink/commands", $payload);
    }

    /**
    Deployments
    */

    /**
    * Get all deployments for a project
    *
    * @param string $permalink permalink of project to return
    *
    * @return array an array of arrays describing deployments
    */
    public function deployments($permalink)
    {
        return $this->get('projects/' . $permalink . '/deployments');
    }

    /**
    * Get a specific deployments for a project
    *
    * @param string $permalink permalink of project to return
    * @param string $uuid      deploy uuid to fetch
    *
    * @return array an array describing the requested deployments
    */
    public function deployment($permalink, $uuid)
    {
        return $this->get('projects/' . $permalink . '/deployments/' . $uuid);
    }

    /**
    * Create a deployment
    *
    * @param string $permalink    permalink of project to return
    * @param string $parent_uuid  server UUID or group UUID to deploy to
    * @param string $start_rev    revision to deploy fron
    * @param string $end_rev      revision to deploy to
    * @param bool   $mode         if TRUE run, FALSE to preview
    * @param bool   $email_notify send email notification
    * @param bool   $copy_config  copy defined config files
    *
    * @return no idea
    */
    public function createDeployment($permalink, $parent_uuid, $start_rev, $end_rev = '', $mode = true, $email_notify = true, $copy_config = true)
    {
        $payload = array(
            'deployment' => array(
                'parent_identifier' => $parent_uuid,
                'start_revision'    => '',
                'end_revision'      => ($end_rev) ? $end_rev : '',//$this->projectLatest($permalink),
                'mode'              => ($mode) ? 'queue' : 'preview',
                'copy_config_files' => ($copy_config) ? 1 : 0,
                'email_notify'      => ($email_notify) ? 1 : 0
            )
        );
        $payload = json_encode($payload);
        return $this->post('projects/' . $permalink . '/deployments', $payload);
    }

    /**
    Server groups
    */

    /**
    * Get Server groups
    *
    * @param string $permalink permalink of project to return
    *
    * @return array of information
    */
    public function serverGroups($permalink)
    {
        return $this->get('projects/' . $permalink . '/server_groups');
    }
}
