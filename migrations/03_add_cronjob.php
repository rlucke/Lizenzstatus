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
            'minute' => '-1'
    );
    
    public function __construct()
    {
        $this->cronjob_md5 = md5(
            self::$cronjob['filename'] .
            self::$cronjob['class'] .
            self::$cronjob['priority'] .
            self::$cronjob['minute']
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
                (:task_id, :filename, :class, '1')",
                array(
                    'task_id' => $this->cronjob_md5,
                    'filename' => self::$cronjob['filename'],
                    'class' => self::$cronjob['class']
                )
            );
            
            $schedule_id = md5(uniqid($this->cronjob_md5, true));
            
            $db->exec(
                "INSERT INTO `cronjobs_schedules`
                (`schedule_id`, `task_id`, `parameters`, `priority`,
                `type`, `minute`, `mkdate`, `chdate`, `last_result`)
                VALUES
                (:schedule_id, :task_id, '[]', :priority,
                'once', :minute, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL)",
                array(
                    'schedule_id' => $schedule_id,
                    'task_id' => $this->cronjob_md5,
                    'priority' => self::$cronjob['priority'],
                    'minute' => self::$cronjob['minute']
                )
            );
            
            $db = null;
        }
    }
    
    
    function down()
    {
        if($this->cronjob_md5) {
            $db = DBManager::get();
            
            $db->exec(
                "DELETE FROM `cronjobs_tasks` WHERE task_id = :cronjob_md5",
                array(
                    'cronjob_md5' => $this->cronjob_md5
                )
            );
            
            $db->exec(
                "DELETE FROM `cronjobs_schedules` WHERE task_id = :cronjob_md5",
                array(
                    'cronjob_md5' => $this->cronjob_md5
                )
            );
            
            $db = null;
        }
    }
}
