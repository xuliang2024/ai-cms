<?php

namespace app\video\model;

use think\Model;

class MigrationTaskModel extends Model
{   
    protected $connection = 'translate';
    protected $table = 'ts_migration_task';
} 