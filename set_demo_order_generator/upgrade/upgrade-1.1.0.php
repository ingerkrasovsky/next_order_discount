<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    return $module instanceof set_demo_order_generator
        && $module->installUsageTable();
}
