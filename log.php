<?php

require_once('eh.php');

$log_file = file_get_contents(LOGGER_LOG_FILE);

echo '['.trim($log_file, ',').']';