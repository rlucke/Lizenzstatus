<?php
class AddDeletingPermissions extends Migration
{
    public function up() {
        DBManager::get()->exec("
        INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
        VALUES
            (
              MD5('MASS_FILE_DELETION_MIN_PERMS'),
              '',
              'MASS_FILE_DELETION_MIN_PERMS',
              'admin',
              '0',
              'string',
              'global',
              'global',
              0,
              UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
              'Defines the minimum permissions for mass file deletion.',
              '', ''
          )
        ");
        }

    public function down() {
        DBManager::get()->exec("
            DELETE FROM `config` 
            WHERE config_id = MD5('MASS_FILE_DELETION_MIN_PERMS')
        ");
    }
}
