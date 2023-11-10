<?php namespace DE\RUB\JSMOAjaxTestExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\Framework;

/**
 * JSMO Ajax Test External Module 
 */
class JSMOAjaxTestExternalModule extends AbstractExternalModule {

    #region Hooks & JS Injection

    function redcap_module_link_check_display($project_id, $link) {
        return $link;
    }

    // Hook - Data Entry pages
    function redcap_data_entry_form ($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1) {
        $this->setupJSMO("Data Entry [PID={$project_id}].");
    }

    // Hook - Survey pages
    function redcap_survey_page ($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
        $this->setupJSMO("Survey [PID={$project_id}].", true);
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

    function setupJSMO($msg, $survey = false) {

        return false; // OFF

        $debug = $this->framework->getSystemSetting("debug") == true;
        $this->framework->initializeJavascriptModuleObject();
        ?>
            <script>
                ;(function() {
                    console.log(<?=json_encode($msg)?>);
                    const isSurvey = <?=$survey ? "true" : "false"?>;
                    const JSMO = <?=$this->framework->getJavascriptModuleObjectName()?>;
                    const data = {
                        one: 'One',
                        two: [
                            1, 2, 3
                        ]
                    };
                    window.make_jsmo_request = function(custom) {
                        data.custom = custom;
                        JSMO.ajax('test', data).then(function(data) {
                            console.log('Module handling: Successful ajax request.', data);
                        }).catch(function(err) {
                            console.error('Module handling: Unsuccessful ajax request:', err);
                        });
                        if (!isSurvey) {
                            JSMO.log('Ajax log without record override', { para1: 1 })
                            .then(function(data) {
                                console.log('Called log() without record override. Response:', data)
                            })
                            .catch(function(err) {
                                console.error('Error when calling log() without record override: ', err)
                            })
                            JSMO.log('Ajax log with record override', { para1: 2, record: '5' })
                            .then(function(data) {
                                console.log('Called log() with record override. Response: ', data)
                            })
                            .catch(function(err) {
                                console.error('Error when calling log() with record override: ', err)
                            })
                        }
                    };
                    window.make_jsmo_request('Initial');
                })();
            </script>
        <?php
    }

    #endregion

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

    #region Handle API Requests

    function redcap_module_api($action, $payload, $project_id, $user_id, $format, $returnFormat, $csvDelim) {

        if ($action == "null") {
            return null;
        }

        if ($action == "exception") {
            throw new \Exception($payload["msg"]);
        }

        if ($action == "error") {
            return $this->framework->apiErrorResponse("My custom not found error message", 404);
        }

        if ($action == "file") {
            // Send a file
            $path = $this->framework->createTempFile();
            file_put_contents($path, "Test file");
            return $this->framework->apiFileResponse($path, "My file.txt", "text/plain");
        }

        $counter_key = "api_counter";

        if ($project_id !== null) {
            // Increment counters
            try {
                $orig = $this->getProjectSetting($counter_key) ?? 0;
                $orig = is_numeric($orig) ? $orig * 1 : 0;
                $this->setProjectSetting($counter_key, $orig + 1);
                $new = $this->getProjectSetting($counter_key) ?? 0;
            }
            catch (\Throwable $ex) {
                $ex_msg = $ex->getMessage();
                return $this->framework->apiErrorResponse($ex_msg);
            }
    
            $msg = "Success in project {$project_id}! Counter updated from {$orig} to {$new}. Custom = {$payload["custom"]}";
        }
        else {
            $msg = "Called '$action' in a non-project context";
        }


        if ($returnFormat == "json") {
            return $this->framework->apiJsonResponse(["msg" => $msg]);
        }
        else if ($returnFormat == "xml") {
            $msg = '<?xml version="1.0" encoding="UTF-8" ?>' .
                '<response>' .
                '<msg><![CDATA['.$msg.']]></msg>' .
                '</response>';
            return $this->framework->apiResponse($msg);
        }
        else {
            return $this->framework->apiCsvFileResponse(["Message" => [$msg]], "Message.csv", $csvDelim);
        }
    }

    #endregion


}