<?php
$loggingRequired = $_MB['PP']['pp5'] == 'true';
$errorHandlingRequired = $_MB['PP']['pp8'] == 'true';

mb_pattern_run_template("MQOnewayAckApp", "mqsi/Request.esql.php", "mqsi/Request.esql");

if ($loggingRequired) {
     mb_pattern_run_template("MQOnewayAckApp", "mqsi/Log.esql.php", "mqsi/Log.esql");
}

if ($errorHandlingRequired) {
     mb_pattern_run_template("MQOnewayAckApp", "mqsi/Error.esql.php", "mqsi/Error.esql");
}
?>