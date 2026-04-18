<?php

namespace app\video\model;

use think\Model;

class WorkflowExecuteRecordModel extends Model
{   
    protected $connection = 'translate';
    protected $table = 'ts_workflow_execute_record';
} 