<?php

/**********************************************************************

 Copyright (C) 2008 kuerant@gmail.com.

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

 **********************************************************************/

class	Demon
{
	private	$__action = NULL;
	private	$__name = NULL;
	private	$__dir_proc = NULL;
	private	$__dir_crash = NULL;
	private	$__pid = 0;
	private	$__pid_file = NULL;
	
	/**
	 * \param action -- action to {boot|kill} daemon
	 * \param name -- name of daemon
	 * \param dir_proc -- directory where pid file locates
	 * \param dir_crash -- directory where crash file locates
	 */
	function	__construct( $action, $name, $dir_proc='/tmp/proc', $dir_crash='/tmp/crash' )
	{/*{{{*/
		$this->__action = $action;
		$this->__name = $name;
		$this->__dir_proc = $dir_proc;
		$this->__dir_crash = $dir_crash;
	}/*}}}*/
	
	function	start()
    {/*{{{*/
        if ( 0 == strcasecmp('kill', $this->__action) ) {
            return	$this->kill();
        } 

        if( !file_exists( $this->__dir_proc ) ) {
            $ok = mkdir( $this->__dir_proc, 0755, true );
            if (!$ok ) {
                error_log( "fail to mkdir '{$this->__dir_proc}'." );
                return	false;
            }
        }

        // daemonize		
        $pid = pcntl_fork();
        if( $pid < 0 ) {
            error_log( "fail to fork to daemonize." );
            exit( 1 );
        }
        elseif ( $pid > 0 ) {
            // parent process 
            exit( 0 );
        }
        // child process 
        $sid = posix_setsid();
        if ( $sid < 0 ) {
            error_log( "fail to setsid()." );
        }

        global	$argv;
        //var_dump( $argv );

        $this->__pid = getmypid();
        $this->__pid_file = sprintf( "%s/%s.%d.pid", $this->__dir_proc, $this->__name, $this->__pid );
        $fp = fopen( $this->__pid_file, "w" );
        if ( !$fp ) {
            error_log( "fail to create PID file '{$this->__pid_file}'." );
            $this->__pid_file = NULL;
            return	false;
        }

        fprintf( $fp, "/usr/local/bin/php ");

        foreach ( $argv as $arg ) {
            fprintf( $fp, "%s ", $arg );
        }
        fwrite( $fp, "\n" );
        fclose( $fp );

        return	true;
    }/*}}}*/

	function	stop()
    {/*{{{*/
        if ( 0 == strcasecmp('kill', $this->__action) ) {
            return	$this->kill();
        } 

        if ( isset( $this->__pid_file ) ) {
            $ok = unlink( $this->__pid_file );
            if ( !$ok ) {
                error_log( "fail to remove PID file '{$this->__pid_file}'." );
            }
        }

        return	true;
    }/*}}}*/
	
	function	kill()
    {/*{{{*/
        if ( empty( $this->__name ) ) {
            error_log( "Bad KILL : no 'name' specified." );
            return	false;
        }
        if ( empty( $this->__dir_proc ) ) {
            error_log( "Bad KILL : no 'dir_proc' specified." );
            return	false;
        }

        $pattern = sprintf( "%s/%s.*.pid", $this->__dir_proc, $this->__name );
        //$pattern_ereg = sprintf( "%s/%s.*\.([0-9]+)\.pid", $this->__dir_proc, $this->__name );
        $pattern_preg = '/.*\.(\d+)\.pid/';
        $pid_files = glob( $pattern );
        foreach ( $pid_files as $pid_file ) {
            //echo "killing ",$pid_file,"...\n";
            $ok = preg_match( $pattern_preg, $pid_file, $matches );
            if ( !$ok ) {
                error_log( "invalid pid file name '{$pid_file}'." );
                continue;
            }
            $pid = $matches[1]; 
            $ok = posix_kill( $pid, 0 );
            if ( $ok ) {
                posix_kill( $pid, SIGTERM );
            }
            else {
                error_log( "PID '{$pid}' for '$pid_file' no response." );
                unlink( $pid_file );
            }
        }

        exit ( 0 );
        //return	true;
    }/*}}}*/
}

function	demonize( $argv , &$stagename, &$cfgfile)
{
	// command line : <cmd>  -{d|k} <daemon name> -p <proc dir> -r <crash dir> -s <stagename> -c <cfgfile>
	
	$action = false;
	$name = false;
	$dir_proc = '/tmp/proc';
	$dir_crash = '/tmp/crash';
	
	$count_argv = count($argv);
	for( $i=0; $i<$count_argv; $i++ ) {
		if ( 0 == strcmp('-d', $argv[$i]) ) {
			$action = 'boot';
			$i++;
			$name = $argv[ $i ];
		}
		elseif ( 0 == strcmp('-k', $argv[$i]) ) {
			$action = 'kill';
			$i++;
			$name = $argv[ $i ];
		}
		elseif ( 0 == strcmp('-p', $argv[$i]) ) {
			$i++;
			$dir_proc = $argv[ $i ];
		}
		elseif ( 0 == strcmp('-r', $argv[$i]) ) {
			$i++;
			$dir_crash = $argv[ $i ];
		}
		elseif ( 0 == strcmp('-s', $argv[$i]) ) {
			$i++;
			$stagename = $argv[ $i ];
		}
		elseif ( 0 == strcmp('-c', $argv[$i]) ) {
			$i++;
			$cfgfile = $argv[ $i ];
		}
	}

	if ( empty($action) || empty($name) ) {
		return	false;
	}
	
	$demon = new Demon( $action, $name, $dir_proc, $dir_crash );
	
	return	$demon;
}

?>
