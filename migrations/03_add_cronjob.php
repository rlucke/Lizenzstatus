<?php

/*
 *  This file is part of the Lizenzstatus plugin.
 * 
 *  Copyright (c) 2016 data-quest <info@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */


class AddCronjob extends Migration {
    
    private static $cronjob = array(
            'filename' => 'public/plugins_packages/data-quest/Lizenzstatus/NewYear2017Cronjob.class.php',
            'class' => 'NewYear2017Cronjob',
            'priority' => 'normal',
            'execution_timestamp' => '1483225200' //2017-01-01 0:00 MEZ (UTC+0100)
    );
    
    public function __construct()
    {
        $this->cronjob_md5 = md5(
            self::$cronjob['filename'] .
            self::$cronjob['class'] .
            self::$cronjob['priority'] .
            self::$cronjob['execution_timestamp']
        );
        
        parent::__construct();
    }
    
    function up()
    {
        if($this->cronjob_md5) {
            $db = DBManager::get();
            
            $db->exec(
                "INSERT INTO `cronjobs_tasks`
                (`task_id`, `filename`, `class`, `active`) 
                VALUES
                ('".$this->cronjob_md5."', '"
                .self::$cronjob['filename']."', '"
                .self::$cronjob['class']."', '1')"
            );
            
            $schedule_id = md5(uniqid($this->cronjob_md5, true));
            
            $db->exec(
                "INSERT INTO `cronjobs_schedules`
                (`schedule_id`, `task_id`, `parameters`, `priority`,
                `type`, `next_execution`, `mkdate`, `chdate`, `last_result`)
                VALUES
                ('".$schedule_id."', '"
                .$this->cronjob_md5."', '[]', '".self::$cronjob['priority']
                ."', 'once', '".self::$cronjob['execution_timestamp']
                ."', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL)"
            );
            
            $db = null;
        }
    }
    
    
    function down()
    {
        if($this->cronjob_md5) {
            $db = DBManager::get();
            
            $db->exec(
                "DELETE FROM `cronjobs_tasks` WHERE task_id = '".$this->cronjob_md5."'"
            );
            
            $db->exec(
                "DELETE FROM `cronjobs_schedules` WHERE task_id = '".$this->cronjob_md5."'"
            );
            
            $db = null;
        }
    }
}
