<?php
$cronDate = exec("drush sget system.cron_lock");
if(!empty($cronDate)){
	if(time() - $cronDate >= 900) {
		echo 'expired';
	}else{
		echo 'ok';
	}
}else{
	echo 'ok';
}