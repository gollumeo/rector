<?php

namespace RectorPrefix20211113;

if (\class_exists('tslib_adminPanelHook')) {
    return;
}
class tslib_adminPanelHook
{
}
\class_alias('tslib_adminPanelHook', 'tslib_adminPanelHook', \false);
