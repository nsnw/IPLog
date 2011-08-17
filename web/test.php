<?php

include('iplog.inc.php');

$i = new IPLog();

//print_r($i->GetChainIDByNameAndSource(array("INTERNET-F-I-H-DESTINY", 2)));
print_r($i->GetAllIPs());
