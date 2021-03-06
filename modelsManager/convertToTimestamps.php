<?php

require_once __DIR__.'/../../shared/connect.php';
require_once __DIR__.'/../../shared/models.php';

foreach ($tablesModels as $tableName => $tableModel) {
	if (isset($tableModel['hasHistory']) && !$tableModel['hasHistory']) {
		continue;
	}
	$stmt = $db->prepare('ALTER TABLE `'.$tableName.'` CHANGE `iVersion` `iVersion` BIGINT(20) NOT NULL;');
	$stmt->execute();
    if(isset($config->db->fractionalTime) && $config->db->fractionalTime) {
       $curVersionQuery = "ROUND(UNIX_TIMESTAMP(CURTIME(2)) * 10)";
    } else {
       $curVersionQuery = "(UNIX_TIMESTAMP() * 10)";
    }
	$stmt = $db->prepare('SELECT '.$curVersionQuery.' INTO @curVersion; UPDATE `'.$tableName.'` SET `iVersion` = @curVersion;');
	$stmt->execute();
	$stmt = $db->prepare("truncate history_$tableName;");
    $stmt->execute();
	// see http://stackoverflow.com/a/31865524/2560906 we need to make a default
	$stmt = $db->prepare('ALTER TABLE `history_'.$tableName.'` CHANGE `iVersion` `iVersion` BIGINT(20) NOT NULL;');
	$stmt->execute();
	$stmt = $db->prepare('ALTER TABLE `history_'.$tableName.'` CHANGE `iNextVersion` `iNextVersion` BIGINT(20) NULL DEFAULT NULL;');
	$stmt->execute();
	$fields = array_keys($tableModel['fields']);
	$fieldsStr = "`".implode('`, `', $fields)."`";
	$fieldsStrWithPrefix = "`".$tableName."`.`".implode("`, `".$tableName."`.`", $fields)."`";
	$query = "INSERT INTO `history_".$tableName."` (`ID`, ".$fieldsStr.", `bDeleted`, `iVersion`, `iNextVersion`) ".
	   "(SELECT `".$tableName."`.`ID`, ".$fieldsStrWithPrefix.", 0 as `bDeleted`, CURRENT_TIMESTAMP as `iVersion`, NULL as `iNextVersion` ".
	    "FROM `".$tableName."` ".
	    "LEFT JOIN `history_".$tableName."` ON (`history_".$tableName."`.`ID` = `".$tableName."`.`ID` AND `history_".$tableName."`.`bDeleted` IS NULL AND `history_".$tableName."`.`iNextVersion` IS NULL)".
	    "WHERE `history_".$tableName."`.`ID` IS NULL)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo " ok\n";
}

echo "Your base is now converted, the last action is to reinsert the triggers by running:\n";
echo "commonFramework/modelsManager/triggers.php\n";
