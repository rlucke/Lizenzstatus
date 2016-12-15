<?php
class InitPlugin extends Migration
{
	function up(){
        DBManager::get()->exec("
        INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
        VALUES
            (MD5('INFO_TEXT_52A'), '', 'INFO_TEXT_52A', '".
                "Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua."
                ."', 0, 'string', 'global', 'global', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Wird im Dateibereich angezeigt für Dozenten.', '', '')
        ");
	}
}
