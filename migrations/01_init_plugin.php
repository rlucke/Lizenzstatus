<?php
class InitPlugin extends Migration
{
	function up(){
        DBManager::get()->exec("
        INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
        VALUES
            (MD5('INFO_TEXT_52A'), '', 'INFO_TEXT_52A', '".
                "Beachten Sie, dass Texte, die urheberrechtlich geschützt und nicht über Campuslizenzen abgedeckt sind, nicht mehr pauschal über die Hochschule vergütet wird. Wenn Sie einen derartigen Text hochladen, müssen Sie theoretisch selbst die VGWort benachrichtigen und die Abrechnung individuell vornehmen."
                ."', 0, 'string', 'global', 'global', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Wird im Dateibereich angezeigt für Dozenten.', '', '')
        ");
	}
}
