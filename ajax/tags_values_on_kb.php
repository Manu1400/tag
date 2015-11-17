<?php
include ('../../../inc/includes.php');

//Note : can merge this file with tags_values.php

echo "<tr>";
echo "<td>";
echo "<label>";
echo "<b>".PluginTagTag::getTypeName(2)."</b>&nbsp;";
PluginTagTag::tagDropdownMultipleSearchKB();
echo "</label>";
echo "</td>";
echo "</tr>";
