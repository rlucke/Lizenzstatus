<?php
class AddDeleting extends Migration
{
	function up(){
        DBManager::get()->exec("
        INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
        VALUES
            (
              MD5('ALLOW_MASS_FILE_DELETING'), 
              '', 
              'ALLOW_MASS_FILE_DELETING', 
              0, 
              0, 
              'boolean', 
              'global', 
              'global', 
              0, 
              UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', '', ''
          )
        ");
	}

	function down() {
        DBManager::get()->exec("
            DELETE FROM `config` 
            WHERE config_id = MD5('ALLOW_MASS_FILE_DELETING')
        ");
    }
}
