<?php

namespace RectorPrefix20210807;

if (\class_exists('Tx_Extbase_Persistence_Exception')) {
    return;
}
class Tx_Extbase_Persistence_Exception
{
}
\class_alias('Tx_Extbase_Persistence_Exception', 'Tx_Extbase_Persistence_Exception', \false);
