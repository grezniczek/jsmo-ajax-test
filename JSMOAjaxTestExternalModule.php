<?php namespace DE\RUB\JSMOAjaxTestExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\Framework;

/**
 * JSMO Ajax Test External Module 
 */
class JSMOAjaxTestExternalModule extends AbstractExternalModule {

    /**
     * EM Framework (tooling support)
     * @var Framework
     */
    private $fw;

    private $debug = false;

    function __construct() {
        parent::__construct();
        $this->fw = $this->framework;
        $this->debug = $this->fw->getSystemSetting("debug") == true;
    }

    #region Hooks

    // Hook - Data Entry pages
    function redcap_data_entry_form ($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1) {
        $this->setupJSMO("Data Entry [PID={$project_id}].");
    }

    // Hook - Survey pages
    function redcap_survey_page ($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
        $this->setupJSMO("Survey [PID={$project_id}].");
    }

    // Hook - All pages
    function redcap_every_page_top($project_id = null) {
        if ($project_id == null) {
            // System page
            $this->setupJSMO("System page");
        }
        else {
            // Project page
            $page = PAGE;

            if (strpos($page, "DataEntry/index.php") !== false) return; // Data Entry - handled elsewhere
            if (strpos($page, "surveys/index.php") !== false && !isset($_GET["sq"])) return; // Surveys (but not Survey Queue) are handled elsewhere

            $this->setupJSMO("Some project page [PID={$project_id}].");
        }
    }

    #endregion


    function setupJSMO($msg) {
        $this->fw->initializeJavascriptModuleObject();
        ?>
            <script>
                ;(function() {
                    console.log(<?=json_encode($msg)?>);
                    const JSMO = <?=$this->fw->getJavascriptModuleObjectName()?>;
                    const data = {
                        one: 'One',
                        two: [
                            1, 2, 3
                        ]
                    };
                    window.make_jsmo_request = function(custom) {
                        data.custom = custom;
                        JSMO.ajax('test', data).then(function(data) {
                            console.log('Successful ajax request.', data);
                        }).catch(function(err) {
                            console.error('Unsuccessful ajax request:', err);
                        });
                        JSMO.log('Ajax log without record override', { para1: 1 })
                        JSMO.log('Ajax log with record override', { para1: 2, record: '5' }).catch(function(err) {
                            // Do nothing
                        })
                    };
                    window.make_jsmo_request('Initial');
                })();
            </script>
        <?php
    }


    #region Handle Ajax Requests

    function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {

        $counter_key = "counter";

        // Increment counters
        try {
            if ($project_id == null) {
                $orig = $this->getSystemSetting($counter_key) ?? 0;
                $orig = is_numeric($orig) ? $orig * 1 : 0;
                $this->setSystemSetting($counter_key, $orig + 1);
                $new = $this->getSystemSetting($counter_key) ?? 0;
            }
            else {
                $orig = $this->getProjectSetting($counter_key) ?? 0;
                $orig = is_numeric($orig) ? $orig * 1 : 0;
                $this->setProjectSetting($counter_key, $orig + 1);
                $new = $this->getProjectSetting($counter_key) ?? 0;
            }
        }
        catch (\Throwable $ex) {
            $ex_msg = $ex->getMessage();
        }

        if ($project_id == null) {
            $msg = "Success outside project context! Counter from {$orig} to {$new}. Custom = {$payload["custom"]}";
        }
        else {
            $msg = "Success in project {$project_id}! Counter from {$orig} to {$new}. Custom = {$payload["custom"]}";
        }
        $log_id = $this->log("Some dummy logging from PHP");
        return array(
            "msg" => $msg,
            "action" => $action,
            "payload" => $payload,
            "project_id" => $project_id,
            "record" => $record,
            "instrument" => $instrument,
            "event_id" => $event_id,
            "group_id" => $group_id,
            "survey_hash" => $survey_hash,
            "response_id" => $response_id,
            "survey_queue_hash" => $survey_queue_hash,
            "repeat_instance" => $repeat_instance,
            "page" => $page,
            "page_full" => $page_full,
            "user_id" => $user_id,
            "exception" => $ex_msg ?? '',
        );
    }

    #endregion

}