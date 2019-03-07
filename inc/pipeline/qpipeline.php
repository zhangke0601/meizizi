<?php

define ('QP_JOIN_SERVICE'   , '/QPipe/appser/qp_join_service.php' );
define ('QP_GETPKG_SERVICE' , '/QPipe/appser/qp_getpkg_service.php' );
define ('QP_CHKGET_SERVICE' , '/QPipe/appser/qp_chkget_service.php' );
define ('QP_CMTGET_SERVICE' , '/QPipe/appser/qp_cmtget_service.php' );
define ('QP_LEAVE_SERVICE'  , '/QPipe/appser/qp_leave_service.php' );

//------------------------------------------------------------------------------------------------
//

function parse_result($__data, &$__result) 
{
	//$__count = 0;

	$__result = split ('\|', $__data);

	/*
	while (list($key, $value) = each ($__result)) 
	{
	    $__count++;
	}
	*/

	return count($__result);
}

function _call_curl($__url)
{

	$__ch = curl_init();

	curl_setopt($__ch, CURLOPT_URL, $__url);
	curl_setopt($__ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($__ch, CURLOPT_TIMEOUT, 60);
	$resp= curl_exec($__ch);

	curl_close($__ch);

	return $resp;
	
}

//------------------------------------------------------------------------------------------------

function _do_join(&$__worker)
{
	$__workerid = -1;

	$__url = sprintf( "%s%s?stagename=%s&provide=%s&consume=%s",
				$__worker['stageinfo']['serverurl'],
				QP_JOIN_SERVICE,
				$__worker['stageinfo']['stagename'],
				$__worker['stageinfo']['provide'],
				$__worker['stageinfo']['consume'] );

	/*
	curl_setopt($__worker['curl_handle'], CURLOPT_URL, $__url);
	curl_setopt($__worker['curl_handle'], CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($__worker['curl_handle']);
	*/

	$response = _call_curl($__url);
	
	$__result = array ();
	
	switch (parse_result($response, $__result)) {
		case 3:
			if (strcasecmp($__result[0], "ok") == 0) {
				$__workerid = $__result[2];
				break;
			}
		default:
			printf("Can not join:(%s) as (%s)\n", $response, $__url);
			break;
		}

	if ($__workerid > 0)
		$__worker['workerid'] = $__workerid;

	return $__workerid;
}

//--------------------------------------------------------------------------------------
// 
/***
 * join_pipeline: join into qpipeline
 * params: $stagename stage'name 
 *         $provide   provide type
 *         $consume   consume type
 *         $serverurl server'url
 * return: == -1, join failed 
 *         != -1, join ok, return $worker 
 */

function join_pipeline($_stagename, $_provide, $_consume, $_serverurl)
{
	$__worker = array ();	

	$__worker['stageinfo']['serverurl'] = $_serverurl;
	$__worker['stageinfo']['consume']   = $_consume;
	$__worker['stageinfo']['provide']   = $_provide;
	$__worker['stageinfo']['stagename'] = $_stagename;
	$__worker['workerid'] = -1;
	//$__worker['curl_handle'] = curl_init();

	if (_do_join($__worker) <= 0) {
		return -1;	
	}

	return $__worker;	
}

//---------------------------------------------------------------------------------------------------
// 
/***
 * leave_pipeline: leave from the qpipeline 
 * params: $worker  worker handle 
 * return: 0   
 */

function leave_pipeline(&$__worker)
{
        $__url = sprintf("%s%s?workerid=%s",
                         $__worker['stageinfo']['serverurl'],
                         QP_LEAVE_SERVICE,
                         $__worker['workerid']);
	

	/*
        curl_setopt($__worker['curl_handle'], CURLOPT_URL, $__url);
	$ret = curl_exec($__worker['curl_handle']);
	*/
	$ret = _call_curl($__url);
	printf("%s\n", $ret);

	//curl_close($__worker['curl_handle']);

        return 0;
}

//---------------------------------------------------------------------------------------------------
//
function try_get_package(&$worker, &$package)
{
        $__ret = -2;

        $__url = sprintf("%s%s?workerid=%s",
                           $worker['stageinfo']['serverurl'],
                           QP_GETPKG_SERVICE,
                           $worker['workerid']);

	/*
        curl_setopt($worker['curl_handle'], CURLOPT_URL, $__url);
	curl_setopt($worker['curl_handle'], CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($worker['curl_handle']);
	*/

	$response = _call_curl($__url);

	$__result = array();

        switch (parse_result($response, $__result)) {
		case 3:
			if (strcasecmp($__result[0], "fail") == 0) {
				$__ret = $__result[1];
				break;
			}
			if (strcasecmp($__result[0], "ok") == 0) {
				$__ret = $__result[1];
				break;
			}
                case 4:
                        if (strcasecmp($__result[0], "ok") == 0) {
                                $package['packageid'] = $__result[2];
                                $package['uri'] = $__result[3];
                                $__ret = $package['packageid'];
                                break;
                        }
                default:
                        printf("Bad response (%s) for (%s)!\n", $response, $__url);
                        break;
        } 

	return $__ret;
}

//----------------------------------------------------------------------------------------------------
//
/***
 * get_package: get a data package from qpipeline
 * params:  $worker  : worker handle 
 *          $package : package to be got 
 * return:  > 0 success
 *          <= 0 no available data package
 */

function get_package(&$worker, &$package)
{
        $__ret = try_get_package($worker, $package);

        if ($__ret == -1) {
                printf("Worker %d timeout, rejoin\n", $worker['workerid']);
                if (_do_join($worker) > 0)
                        $__ret = try_get_package($worker, $package);
                else
                        printf("Rejoin failed. \n");
        }

        return $__ret;
}

//----------------------------------------------------------------------------------------------------
//

function do_chkget(&$worker, &$package)
{
        $__ret = -2;
        $__url = sprintf("%s%s?workerid=%s&packageid=%s&size=%s&checksum=%s",
                                        $worker['stageinfo']['serverurl'],
                                        QP_CHKGET_SERVICE,
                                        $worker['workerid'],
                                        $package['packageid'], $package['size'], $package['checksum']);
/*
        curl_setopt($worker['curl_handle'], CURLOPT_URL, $__url);
	curl_setopt($worker['curl_handle'], CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($worker['curl_handle']);
	*/

	$response = _call_curl($__url);

	$__result = array();

        switch (parse_result($response, $__result)) {
                case 3:
                        if (strcasecmp($__result[0], "ok") == 0) {
                                $package['items'] = $__result[2];
                                $__ret = 0;
                                break;
                        }
                        if (strcasecmp($__result[0], "fail") == 0)
                                $__ret = $__result[1];
                default:
                        printf("Bad response (%s) for (%s)!\n", $response, $__url);
                        break;
        }

        return $__ret;
}

//--------------------------------------------------------------------------------------------------
//
/***
 * check_get: check if get package right
 * params:    $worker  worker handle 
 *            $package package to be checked 
 * return:    0 success
 *            -1 bad package
 */

function check_get(&$worker, &$package)
{                                       
        $__ret = do_chkget($worker, $package);
                                        
        if ($__ret == -1) {              
                printf("Worker %s timeout, rejoin\n", $worker['workerid']);
                if (_do_join($worker) > 0)
                        $__ret = do_chkget($worker, $package);
                else
                        printf("Rejoin failed. \n");
        }
        
        return $__ret;
}     

//--------------------------------------------------------------------------------------------------
//

function do_cmtget(&$worker, &$package, &$newpack)
{
        $__ret = -2;

	if ( !isset( $package['packageid'] ) ) {
	     $package[ 'packageid' ] = '';
        }

        $__url = sprintf("%s%s?workerid=%s&uri=%s&size=%s&checksum=%s&items=%s&packageid=%s",
                         $worker['stageinfo']['serverurl'],
                         QP_CMTGET_SERVICE,
                         $worker['workerid'],
                         $package['uri'], $package['size'], $package['checksum'], $package['items'],
                         $package['packageid']);

	/*
    curl_setopt($worker['curl_handle'], CURLOPT_URL, $__url);
	curl_setopt($worker[ 'curl_handle' ], CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($worker['curl_handle']);
	*/

	$response = _call_curl($__url);
	
	$__result = array();

	switch (parse_result($response, $__result)) {
		case 3:
			if (strcasecmp($__result[0], "fail") == 0) {
				$__ret = $__result[1];
				break;
			}
		case 5:
			if (strcasecmp($__result[0], "ok") == 0) {
				$__ret = $__result[1];
				$package['packageid'] = $__result[2];
				if ($__ret > 0) {
					$newpack['packageid'] = $__result[3];
					$newpack['uri'] = $__result[4];
				}
				break;
			}
		default:
			printf("Bad response (%s) for (%s)!\n", $response, $__url);
			break;
	}

	if ($__ret == -2) {
		printf("can not cmtget:server resp(%s) for(%s)", $response, $__url);	
	}

    return $__ret;
}

//--------------------------------------------------------------------------------------------------
//
/***
 * commit_and_get: commit a generated package and ask for a new package
 * params: $worker   handle of worker 
 *         $package  package to be committed 
 *         $newpack  package will get 
 * return: >= 0 success
 *         -1002 commit success but no new package
 *         <0 && != -1002 commit failed
 */

function commit_and_get(&$worker, &$package, &$newpack)
{
        $__ret = do_cmtget($worker, $package, $newpack);

        if ($__ret == -1) {
                printf("Worker %s timeout, rejoin\n", $worker['workerid']);
                if (_do_join($worker) > 0)
                        $__ret = do_cmtget($worker, $package, $newpack);
                else
                        printf("Rejoin failed. \n");
        }
        return $__ret;
}

//--------------------------------------------------------------------------------------------------

?>
