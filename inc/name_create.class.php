<?php
//
//  处理各种命名,最后生成360自己的名称
//

/**
 * 360名称生成类
 *
 */
class NameCreate
{
	
	/*
	  功能: 根据类型调用名称转换函数并返回结果
	
	*/
	public function processVirusName( $strName , $type )
	{
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		switch( $type )  
		{
			case 'king': 			$result	= $this->processKingName( $strName ); 		break ;
			case 'kingback': 		$result	= $this->processKingName( $strName ); 		break ;
			case 'kaba': 			$result	= $this->processKabaName( $strName ); 		break ;
			case 'rsing': 			$result	= $this->processRsingName( $strName ); 		break ;
			case 'norton': 			$result	= $this->processNortonName( $strName ); 		break ;
			case 'antian': 			$result	= $this->processAntianName( $strName ); 		break ;
			case 'bd': 				$result	= $this->processBitdefenderName( $strName ); 		break ;	 
			case 'nod': 			$result	= $this->processnod32Name( $strName ); 		break ;
			case 'kv': 				$result	= $this->processKvName( $strName ); 		break ;
			case 'cillin': 			$result	= $this->processCillinName( $strName ); 		break ;
		}
		
		return $result ;
	}
	
	/*
	  功能： 根据卡巴名称生成360名称
	
	  type name : 卡巴
	  表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
	  具体条目	: 
	  			  Trojan.Win32.BHO.bn||病毒类型.平台.木马名称.变种||Trojan/Win32.BHO.bn||病毒类型/平台.病毒名称.变种
	  			  Trojan-PSW.Win32.OnLineGames.acf||病毒类型.平台.木马名称.变种||Trojan-PSW/Win32.OnLineGames.acf||病毒类型/平台.病毒名称.变种
	  			  not-a-virus:AdWare.Win32.Look2Me.g||病毒类型.平台.木马名称.变种||AdWare/Win32.Look2Me.g||病毒类型/平台.病毒名称.变种||分号":"前面包括分号本身的字全部去掉
	  			  Virus.Win32.Drowor.a||病毒类型.平台.病毒名.变种||Virus/Win32.Drowor.a||病毒类型/平台.病毒名称.变种
	  			  Win32.Hupigon.dev||平台.病毒名.变种||Win32.Hupigon.dev||平台.病毒名称.变种
	  			  Worm.Win32.Viking.bb||病毒类型.平台.病毒名.变种||Worm/Win32.Viking.bb||病毒类型/平台.病毒名称.变种||worm:蠕虫病毒不作为木马导出	
	  			  
	  	注：卡巴的命名转换时，主要是在病毒类型与平台之间，把“逗号”分隔更改为“/”；分号“:”及其分号前的数据信息全部去掉。
	*/
	public function processKabaName( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
				
				//三段的
				if( count( $name_arr ) == 3 )
				{
					//不做处理
					$result['errno']	= 1 ;
					$result['vname']	= $strName ;
				}
				else if( count( $name_arr ) == 4 )
				{
					if( $name_arr[1] == 'Win32' )
					{
						//第一段中含有 ： 号的						
						if( strpos( $name_arr[0], ":") )
						{
							$name_stack_1_arr	= explode( ":", $name_arr[0] ) ;
							$str_arr_temp[0]	= $name_stack_1_arr[1] . "/" . $name_arr[1]	;			//去掉： 号前面的内容
							$str_arr_temp[1]	= $name_arr[2] ;
							$str_arr_temp[2]	= $name_arr[3] ;
						}
						else 
						{
							$str_arr_temp[0]	= $name_arr[0] . "/" . $name_arr[1] ;
							$str_arr_temp[1]	= $name_arr[2] ;
							$str_arr_temp[2]	= $name_arr[3] ;
						}
						
						$result['errno']	= 1 ;
						$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
					}
				}
				else  if( count( $name_arr ) == 5 )
				{
					$str_arr_temp[0]	= $name_arr[0] . "/" . $name_arr[1] ;
					$str_arr_temp[1]	= $name_arr[2] ;
					$str_arr_temp[2]	= $name_arr[3] ;
					$str_arr_temp[3]	= $name_arr[4] ;
					
					$result['errno']	= 1 ;
					$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
				}
				else 
				{
					$result['vname']	= $strName ;
				}
			}
		}
		
		return $result ;
		
	}
	
	
	/*
	  功能： 根据金山名称生成360名称
	
	  type name : 金山
	  表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
	  具体条目	: 
	  			  Win32.TrojDownloader.Agent.49152||平台.病毒类型.病毒名称.文件大小||TrojanDownloader/Win32.Agent||病毒类型/平台.病毒名称.变种||文件大小：最后的数字部分舍掉。
				  Win32.Troj.Delf.174080||平台.病毒类型.病毒名称.文件大小||Trojan/Win32.Delf||病毒类型/平台.病毒名称.变种||"Troj"改成"Trojan"
				  Worm.MYGOD.a.30001||病毒类型.病毒名称.变种.文件大小||Worm/Win32.MYGOD.a||病毒类型/平台.病毒名称.变种
				  Win32.KLdown.c.17560||平台.病毒名称.变种.文件大小||Win32.KLdown.c||平台.病毒名称.变种 
				  Packes.MaskPE.a||病毒类型.病毒名称.变种||Packes/Win32.MaskPE.a||病毒类型/平台.病毒名称.变种||在病毒类型后面增加入平台
				  
			注：金山命名转换时，主要是把命名最后的文件大小去掉，同时把平台放到病毒类型后面，病毒类型与平台之间的分隔更改为“/”

	*/
	public function processKingName( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
				
				//三段的
				if( count( $name_arr ) == 3 )
				{
					$str_arr_temp[0]	= $name_arr[0] . "/Win32"  ;
					$str_arr_temp[1]	= $name_arr[1] ;
					if( !is_numeric( trim( $name_arr[2] ) ) )
					{
						$str_arr_temp[2]	= $name_arr[2] ; 
					}
				}
				else if( count( $name_arr ) == 4 )
				{
					if( $name_arr[0] == 'Win32' )
					{
						//
						if( $name_arr[1] == 'Troj' )
						{
							$str_arr_temp[0]	= "Trojan/" . $name_arr[0] ;
						}
						else 
						{
							$str_arr_temp[0]	= $name_arr[1] . "/" . $name_arr[0] ;
						}
						
						$str_arr_temp[1]	= $name_arr[2] ;
					}	
					else if( $name_arr[0] == 'Worm' ) 
					{
						$str_arr_temp[0]	= $name_arr[0] . "/Win32" ;
						$str_arr_temp[1]	= $name_arr[1] ;
						$str_arr_temp[2]	= $name_arr[2] ;
					}
				}
				else if( count( $name_arr ) == 5 ) 
				{
					if( $name_arr[0] == 'Win32' )
					{
						if( $name_arr[1] == 'Troj' )
						{
							$str_arr_temp[0]	= "Trojan/" . $name_arr[0] ;
						}
						else 
						{
							$str_arr_temp[0]	= $name_arr[1] . "/" . $name_arr[0] ;
						}
						$str_arr_temp[1]	= $name_arr[2] ;
						$str_arr_temp[2]	= $name_arr[3] ;
					}
				}
				
				$result['errno']	= 1 ;
				$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
			}
		}
		
		return $result ;		
		
	}
	
	
	/*
	  功能： 根据瑞星名称生成360名称
	
	  type name : 瑞星
	  表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
	  具体条目	: 
	  			  Trojan.DL.Win32.Agent.wsz||病毒类型.子类型.平台.病毒名称.变种||Trojan-Download/Win32.Agent.wsz||病毒类型/平台.病毒名称.变种||病毒类型与子类型合并，通过“-”合并,DL转化为全称：Download
				Trojan.PSW.Win32.OnlineGames.dre||病毒类型.子类型.平台.病毒名称.变种||Trojan-PSW/Win32.OnlineGames.dre||病毒类型/平台.病毒名称.变种
				Virus.Win32.SmallDL.a||病毒类型.平台.病毒名称.变种||Virus/Win32.SmallDL.a||病毒类型/平台.病毒名称.变种
				Worm.Agent.xo||病毒类型.病毒名.变种||Worm/Win32.Agent.xo||病毒类型/平台.病毒名称.变种
				Trojan.Win32.Dodolook.au||病毒类型.平台.病毒名称.变种||Trojan/Win32.Dodolook.au||病毒类型/平台.病毒名称.变种
				Win32.Brontok.a||平台.病毒名.变种||Win32.Brontok.a||平台.病毒名.变种
				Packer.Mian007||病毒类型.病毒名||Packer/Win32.Mian007||病毒类型/平台.病毒名称
				Trojan.PSW.QQPass.pmw||病毒类型.子类型.病毒名称.变种||Trojan-PSW/Win32.QQPass.pmw||病毒类型/平台.病毒名称.变种
				Trojan.VB.vvu||病毒类型.病毒名称.变种||Trojan/Win32.VB.vvu||病毒类型/平台.病毒名称.变种
				
		注：瑞星命名转换时，由于瑞星命名病毒类型分为病毒类型与子类型，360需把病毒类型与子类型进行合并，合并采用“-”进行连接符号；病毒类型与平台之间的分隔更改为“/”；子类型中的"DL"缩写更改为"Download"
				
	*/
	public function processRsingName( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
				
				//2段的
				if( count( $name_arr ) == 2 )
				{
					$str_arr_temp[0]	= $name_arr[0] . "/Win32"  ;
					$str_arr_temp[1]	= $name_arr[1] ;
				}
				else if( count( $name_arr ) == 3 )
				{
					if( $name_arr[0] = 'Win32' )
					{
						$str_arr_temp[0]	= $name_arr[0] ;
					}
					else 
					{
						$str_arr_temp[0]	= $name_arr[0] . "/Win32"  ;
					}					
					$str_arr_temp[1]	= $name_arr[1] ;
					$str_arr_temp[2]	= $name_arr[2] ;
				}
				else if( count( $name_arr ) == 4 )
				{
					if( $name_arr[1] == 'Win32' )
					{
						$str_arr_temp[0]	= $name_arr[0] . "/" . $name_arr[1] ;
					}	
					else 
					{
						$str_arr_temp[0]	= $name_arr[0] . "-" . $name_arr[1] . "/Win32"  ;
					}
					$str_arr_temp[1]	= $name_arr[2] ;
					$str_arr_temp[2]	= $name_arr[3] ;
				}
				else if( count( $name_arr ) == 5 )
				{
					if( $name_arr[1] == 'DL' )
					{
						$str_arr_temp[0]	= $name_arr[0] . "-Download/" . $name_arr[2]  ;
					}
					else 
					{
						$str_arr_temp[0]	= $name_arr[0] . "-" . $name_arr[1] . "/" . $name_arr[2]  ;
					}
					$str_arr_temp[1]	= $name_arr[3] ;
					$str_arr_temp[2]	= $name_arr[4] ;
				}
				
				$result['errno']	= 1 ;
				$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
				
			}
		}
		
		return $result ;		
		
	}
	
	
	/*
	   功能： 根据Bitdefender名称生成360名称
	
	  type name : Bitdefender
	  表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
	  具体条目	: 
	  			 Dropped:Generic.Malware.dld!.1D92B3C6||类型.病毒名.变种.特殊标志||Generic/Win32.Malware.dld||病毒类型/平台.病毒名称.变种||分号":"前面包括分号本身的字全部去掉；特殊标记去掉
				Generic.Onlinegames.2.B87B0B9F||类型.病毒名.变种.特殊标志||Generic/Win32.Onlinegames.2||病毒类型/平台.病毒名称.变种
				Trojan.Peed.IBY||病毒类型.病毒名.变种||Trojan/Win32.Peed.IBY||病毒类型/平台.病毒名.变种
				Trojan.PWS.Onlinegames.AYD||病毒类型.子类型.病毒名.变种||Trojan-PWS/Win32.Onlinegames.AYD||病毒类型/平台.病毒名.变种
				Win32.Worm.Viking.IZ||平台.病毒类型.病毒名.变种||Worm/Win32.Viking.IZ||病毒类型/平台.病毒名.变种
				Win32.Almsfir.VB.A||平台.病毒类型.病毒名.变种||Almsfir/Win32.VB.A||病毒类型/平台.病毒名.变种
				BehavesLike:Win32.ExplorerHijack||平台.病毒名||Win32.ExplorerHijack||平台.病毒名||分号":"前面包括分号本身的字全部去掉
				
		注：bitdefender命名转换时，需要去掉“:”及其分号前面的数据信息；去掉特殊标志信息；对于其命名中有特殊字符“！”的直接去掉。
				
	*/
	public function processBitdefenderName( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ":", $strName ) ;
				if( count( $name_arr ) == 2 )
				{
					$name_arr	= $name_arr[1] ;
				}
				else 
				{
					$name_arr	= $name_arr[0] ;
				}
				$name_arr	= explode( ".", $name_arr ) ;
				
				//三段的
				if( count( $name_arr ) == 2 )
				{
					$str_arr_temp[0]	= $name_arr[0] 	;	
					$str_arr_temp[1]	= $name_arr[1] ;
				}
				else if( count( $name_arr ) == 3 )
				{
					$str_arr_temp[0]	= $name_arr[0] . "/Win32" ;	
					$str_arr_temp[1]	= $name_arr[1] ;	
					$str_arr_temp[2]	= $name_arr[2] ;	
				}
				else if( count( $name_arr ) == 4 )
				{
					if( $name_arr[0] == "Win32" )
					{
						$str_arr_temp[0]	= $name_arr[1] . "/" . $name_arr[0] ;	
						$str_arr_temp[1]	= $name_arr[2] ;	
						$str_arr_temp[2]	= $name_arr[3] ;
					}
					else 
					{
						//包含 "Trojan" 的
						if( $name_arr[0] == "Trojan" )
						{
							$str_arr_temp[0]	= $name_arr[0] . "-" . $name_arr[1] . "/Win32";	
							$str_arr_temp[1]	= $name_arr[2] ;	
							$str_arr_temp[2]	= $name_arr[3] ;
						}
						else 
						{
							$str_arr_temp[0]	= $name_arr[0] . "/Win32";	
							$str_arr_temp[1]	= $name_arr[1] ;	
							$str_arr_temp[2]	= str_replace( "!", "", $name_arr[2] ) ;	// 去掉特殊符号
						}
					}
					
				}
				
				$result['errno']	= 1 ;
				$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
			}		
			
		}
		
		return $result ;	
		
	}
	
	
	/*
	  功能： 根据nod32名称生成360名称
	
	  type name : nod32
	  表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
	  具体条目	: 
	  			  Win32/TrojanDownloader.Zlob.BAP||平台/病毒类型.病毒名称.变种||TrojanDownloader/Win32.Zlob.BAP||病毒类型/平台.病毒名.变种
				  Win32/Drowor.NAB||  ||Win32.Drowor.NAB||平台.病毒名称.变种
				  
		  注：nod32命名转换时，把病毒类型与平台调换位置，同时使用“/”作为分隔。
				  
	*/
	public function processnod32Name( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
				
				//三段的
				if( count( $name_arr ) == 1 )
				{
					$name_stack_1_arr	= explode( "/", $name_arr[0] ) ;
					$str_arr_temp[0]	= $name_stack_1_arr[0] ;	
					$str_arr_temp[1]	= $name_stack_1_arr[1] ;
				}
				else if( count( $name_arr ) == 2 )
				{
					$name_stack_1_arr	= explode( "/", $name_arr[0] ) ;
					$str_arr_temp[0]	= $name_stack_1_arr[0] 	;	
					$str_arr_temp[1]	= $name_stack_1_arr[1] ;
					$str_arr_temp[2]	= $name_arr[1] ; 		
				}
				else if( count( $name_arr ) == 3 )
				{
					$name_stack_1_arr	= explode( "/", $name_arr[0] ) ;
					$str_arr_temp[0]	= $name_stack_1_arr[1] . "/" . $name_stack_1_arr[0] ;	
					$str_arr_temp[1]	= $name_arr[1] ;	
					$str_arr_temp[2]	= $name_arr[2] ;	
				}
				
				$result['errno']	= 1 ;
				$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
			}		
			
		}
		
		return $result ;
		
	}
	
	
	/*
	  功能： 根据诺顿名称生成360名称
	
	  type name : 诺顿
	  表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
	  具体条目	: 
	  			  	Trojan||病毒类型||Trojan/Win32||病毒类型/平台
					W32.Wullik@mm||平台.病毒名@变种||Win32.Wullik@mm||平台.病毒名.变种||W32需要更改为Win32
					W32.Mumawow.F||平台.病毒名.变种||Win32.Mumawow.F||平台.病毒名.变种
					W32.Drom||平台.病毒名||Win32.Drom||平台.病毒名.变种
					Infostealer.Gampass||病毒类型.病毒名称||Infostealer/Win32.Gampass||病毒类型/平台.病毒名称
					Downloader||病毒类型||Downloader/Win32||病毒类型/平台
					W32.Spybot.Worm||平台.病毒名.病毒类型||Worm/Win32.Spybot||病毒类型/平台.病毒名称
	  			  
	  	注：诺顿的命名，由于诺顿命名太过笼统，分区不细致，因此放为最后，命名中只有“病毒类型”的需要加入“平台”用“/”分隔；W32平台需要更改为“Win32”；带有“worm”的病毒命名，把病毒类型调整到最前面，然后是平台与病毒名称
	  			  
	*/
	public function processNortonName( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
				
				//三段的
				if( count( $name_arr ) == 1 )
				{
					$str_arr_temp[0]	= $name_arr[0] . "/Win32" ;		
				}
				else if( count( $name_arr ) == 2 )
				{
					if( $name_arr[0] == "W32" )
					{
						$str_arr_temp[0]	= "Win32" ;						
					}
					else 
					{
						$str_arr_temp[0]	= $name_arr[0] . "/Win32" ;		
					}
					$str_arr_temp[1]	= $name_arr[1] ;	
				}
				else if( count( $name_arr ) == 3 )
				{
					if( $name_arr[2] == 'Worm' )
					{
						if( $name_arr[0] == "W32" )
						{
							$str_arr_temp[0]	= "Worm/Win32" ;		
							$str_arr_temp[1]	= $name_arr[1] ;	
						}
					}
					else 
					{
						if( $name_arr[0] == "W32" ) 
						{
							$str_arr_temp[0]	= "Win32" ;		
							$str_arr_temp[1]	= $name_arr[1] ;	
							$str_arr_temp[2]	= $name_arr[2] ;	
						}
						else if( $name_arr[1] == "W32" )
						{
							$str_arr_temp[0]	= "Win32" ;		
							$str_arr_temp[1]	= $name_arr[0] ;	
							$str_arr_temp[2]	= $name_arr[2] ;
						}
						else 
						{
							$str_arr_temp[0]	= $name_arr[0] . "/Win32" ;		
							$str_arr_temp[1]	= $name_arr[1] ;	
							$str_arr_temp[2]	= $name_arr[2] ;
						}
					}
				}
				
				$result['errno']	= 1 ;
				$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
			}		
			
		}
		
		return $result ;
		
	}
	
	
	/*
	  功能： 根据安天名称生成360名称
	  
	  type name : 安天
	  表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
	  具体条目	: 
	  			  Trojan/Win32.Zlob.b[Downloader]||病毒类型/平台.病毒名称.变种||Trojan/Win32.Zlob.b||病毒类型/平台.病毒名称.变种||"[]"中括号中的内容去掉

	  	注：安天的命名与我们大致一样，只需要把“[]”中的内容以及中括号一同去掉即可。（暂停）	  
	  	
	*/
	public function processAntianName( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
			
				if( count( $name_arr ) == 2 )
				{
					$name_stack_1_arr	= explode( "/", $name_arr[0] ) ;
					$str_arr_temp[0]	= $name_stack_1_arr[0] . "/Win32" ;
					$str_arr_temp[1]	= $name_stack_1_arr[1] ;		//去掉[]的内容 
				}
				else if( count( $name_arr ) == 3 )
				{
					$str_arr_temp[0]	= $name_arr[0] 	;	
					$str_arr_temp[1]	= $name_arr[1] ;
					$name_stack_1_arr	= explode( "[", $name_arr[2] ) ;
					$str_arr_temp[2]	= $name_stack_1_arr[0] ;		//去掉[]的内容
				}
				
				$result['errno']	= 1 ;
				$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
			}			
		}
		
		return $result ;
		
	}
	
	/*
		功能: 根据江民名称生成360名称
	
		type name : 江民 kv
		表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注 
		具体条目	: 
			TrojanSpy.Agent.sl	病毒类型.病毒名.变种	TrojanSpy/win32.Agent.sl	病毒类型/平台.病毒名.变种	添加“平台信息”-“Win32”
			Adware/Adload.ad	病毒类型/病毒名称.变种	Adware/Win32.Adload.ad	病毒类型/平台.病毒名.变种	添加“平台信息”-“Win32”
			Trojan/BHO.Gen	病毒类型/病毒名称.变种	Trojan/Win32.BHO.Gen	病毒类型/平台.病毒名.变种	…
			Backdoor/Huigezi.2006.fhd	病毒类型/病毒名称.病毒日期.变种	Backdoor/win32.Huigezi.2006.fhd	病毒类型/平台.病毒名称.病毒日期.变种	…
			Adware/Qnsou	病毒类型/病毒名称	Adware/win32.Qnsou	病毒类型/平台.病毒名称	…
			TrojanDownloader.Agent.hge	病毒类型.病毒名称.变种	TrojanDownloader/Win32.Agent.hge	病毒类型/平台.病毒名.变种	
			I-Worm/Nuwar.a	病毒类型.病毒名称.变种	I-Worm/win32.Nuwar.a	病毒类型/平台.病毒名.变种	过滤，不入木马库
			Trojan/PSW.QQRobber.im	病毒类型/子类型.病毒名称.变种	Trojan/Win32.PSW.QQRobber.im	病毒类型/平台.子类型.病毒名称.变种	
			Win32/Alaqq.1371	平台/病毒名称.变种	Win32.Alaqq.1371	平台.病毒名称.变种	
			Worm/Viking.aeo	病毒类型.病毒名称.变种	Worm/win32Viking.aeo	病毒类型/平台.病毒名.变种	过滤，不入木马库
			注：所有命名方式为：“病毒类型/病毒名称”  “病毒类型/病毒名称.变种” “病毒类型/病毒名.病毒日期.变种” “病毒类型/病毒名” 这四种命名的，“/”后面都要添加 平台信息 "Win32" .
	*/ 
	public function processKvName( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
				
				if( !empty( $name_arr ) )
				{
					foreach ( $name_arr as $idx => $name_info )
					{
						if( $idx == 0 )
						{
							if( strpos( $name_info, "/") )
							{
								$name_stack_1_arr	= explode( "/", $name_info ) ;
								if( $name_stack_1_arr[0] != 'Win32' )
								{
									$str_arr_temp[]	= $name_stack_1_arr[0] . "/Win32" ;
								}
								else 
								{
									$str_arr_temp[]	= "Win32" ;
								}
								$str_arr_temp[]	= $name_stack_1_arr[1] ;
							}
							else 
							{
								$str_arr_temp[]	= $name_info . "/Win32" ;
							}
						}
						else  
						{
							$str_arr_temp[]	= $name_info ;
						}
					}
					
					$result['errno']	= 1 ;
					$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
				}		
			}			
		}
		
		return $result ;
		
	}
	
	/*
		功能: 根据趋势名称生成360名称
	
		type name : 趋势 cillin
		表头		: 厂商命名||厂商命名规则||360命名实例||360命名规则||备注
		具体条目	: 
			Adware_Boran	病毒类型_病毒名称	Adware/Win32.Boran	病毒类型/平台.病毒名称	
			PE_LOOKED.TN	病毒类型_病毒名称.变种	PE/Win32.LOOKED.TN	病毒类型/平台.病毒名称.变种	
			PE_LOOKED.UN-O	病毒类型_病毒名称.变种-不明确此字段	PE/Win32.LOOKED.UN	病毒类型/平台.病毒名称.变种	“ -不明确此字段”去掉，此字段不添加不转换
			Possible_MLWR-1	病毒类型_病毒名称-不明确此字段	Possible/Win32.MLWR	病毒类型/平台.病毒名称-不明确此字段	…
			PE_LOOKED.O-O	病毒类型_病毒名称-不明确此字段	PE/Win32.LOOKED.O	病毒类型/平台.病毒名称-不明确此字段	…
			TROJ_Generic	病毒类型_病毒名称	Trojan/Win32.Generic	病毒类型/平台.病毒名称	
			Possible_Legmir3	病毒类型_病毒名称	Possible/Win32.Legmir3	病毒类型/平台.病毒名称	
			Suspicious_File	病毒类型_文件类型			此数据过滤，不入木马库；
			WORM_SDBOT.CNF	病毒类型_病毒名称.变种	WORM/Win32.SDBOT.CNF	病毒类型/平台.病毒名称.变种	
			注：1、由于趋势大部分命名为大写，为统一格式，请帮忙做如下转换：每个词的第一字母为大写，其余为小写,最后一个词全部为小写（例如：TROJ_DLOADER.NFM 转换成 Troj_Dloader.nfm）。 2、TROJ更改为全称：Trojan ； 3、过滤关键字中再加入“Suspicious”怀疑的，可疑的；										
	*/
	public function processCillinName( $strName ) 
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	array( 'errno' => 0/1, 'vname' => '' )		错误信息及名称		0 -- 不是木马或转换失败  1 -- 转换成功 
		 *
		 */ 
		
		$result	= array(
			'errno'		=> '0',
			'vname'		=> '',
		) ;
		
		if( empty( $strName ) )
		{
			return $result ;
		}
		else 
		{
			// 如果含有一些关键字 ， 则直接return
			if( $this->keywordFilter( $strName ) )
			{
				$result['vname']	= $strName ;
			}
			else 
			{
				$str_arr_temp	= array() ;			//生成的名称数组
				//分割字符串
				$name_arr	= explode( ".", $strName ) ;
			
				if( !empty( $name_arr ) )
				{
					$count	= count( $name_arr ) ;
					foreach ( $name_arr as $idx => $name_info )
					{
						if( $idx == 0 )
						{
							if( strpos( $name_info, "_") )
							{
								$name_stack_1_arr	= explode( "_", $name_info ) ;
								if( $name_stack_1_arr[0] == "PE" )
								{
									$str_arr_temp[]	= $name_stack_1_arr[0] . "/Win32" ;
								}
								else if( $name_stack_1_arr[0] == "TROJ" )
								{
									$str_arr_temp[]	= "Trojan/Win32" ;
								}
								else 
								{
									$str_arr_temp[] = $this->strConvertTo( $name_stack_1_arr[0] ). "/Win32" ;
								}
								
								$name_stack_2_arr	= explode( "-", $name_stack_1_arr[1] ) ;
								$str_arr_temp[]	= $this->strConvertTo( $name_stack_2_arr[0] ) ;
							}
							else 
							{
								$str_arr_temp[]	= $this->strConvertTo( $name_info ) . "/Win32" ;
							}
						}
						else if( $idx == ($count-1) )   //最后一位全部小写
						{
							$name_stack_1_arr	= explode( "-", $name_info ) ;
							$str_arr_temp[]	= strtolower( $name_stack_1_arr[0] ) ;
						}
						else 
						{
							$str_arr_temp[]	= $this->strConvertTo( $name_info ) ;
						}
						
					}
					
					$result['errno']	= 1 ;
					$result['vname']	= $this->array_to_name( $str_arr_temp ) ;
				}	
			}			
		}
		
		return $result ;
		
	}
	
	/*
		功能： 关键字判断
			  如果有以下关键词，即不作为木马数据导出。
		
		Crack||金山产品版||Win32.Troj.CrackCiba.b.686080
		RavFree||瑞星保姆||Win32.Hack.RavFree.pq.353280、Win32.NotVirus.RavFree.qx.884736
		RiskWare||风险程序||Win32.RiskWare.DownWinFixer.u.84738
		worm||蠕虫病毒||W32.Spybot.Worm （诺顿命名）、W32.Rontokbro@mm （诺顿命名）、Worm.Win32.Viking.ls
		Email-Worm||蠕虫病毒||Email-Worm.Win32.Runouce.b（卡巴命名）
		Virus||病毒||Virus.Win32.Delf.bz
	
	*/
	public function keywordFilter( $strName )
	{
		/**
		 *  [in]	--	$strName		病毒名称
		 *  [out]	--	true/false
		 */
		
		// 名称字符串白名单
		$str_keyword	= "/(Crack|crack|RavFree|RiskWare|riskware|worm|Worm|WORM|Email-Worm|Virus|virus|Virut|Parite|Suspicious|Sniffer|CCProxy|CrackCiba|Unknown)/" ;   //i 标识不区分大小写
		
		if( empty( $strName ) )
		{
			return true ;
		}
		
		if( preg_match( $str_keyword, $strName ) ) 
		{
			return true ;
		}
		
		return false ;		
	}
	
	/**
	 * 
	 *	根据数组生成木马名称
	 *
	 */
	public function array_to_name( $name_arr )
	{
		/*
			[in]	-- 		$name_arr = array()  数组	
			[out]	--  	strname	 木马名称
		*/
		$strname	= "" ;
		
		if( !empty( $name_arr ) )
		{
			$count	= count( $name_arr ) ;
			foreach ( $name_arr as $id => $datainfo )
			{
				if( $count == $id+1 ) 
				{
					$strname	.= $datainfo ;
				}
				else 
				{
					$strname	.= $datainfo . "." ;
				}				
			}
		}
		
		return $strname ;		
	}
	
	/**
	 * 转换名称为第一个字母大写,其余小写
	 */
	public function strConvertTo( $strname )
	{
		$result	= "" ;
		if( !empty( $strname ) )
		{
			$strname	= strtolower( $strname ) ;
			$len		= strlen( $strname ) ;
			$strhead	= strtoupper( substr( $strname, 0, 1 ) ) ;
			$strtail	= substr( $strname, 1 ) ;
			
			$result	= $strhead . $strtail ;			
		}
		
		return $result ;
	}
	
}



?>
