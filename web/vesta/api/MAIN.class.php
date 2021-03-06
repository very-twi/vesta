<?php

/**
 * Main entity class
 * Provides usefull methods (utils), shared for sub entities (DNS, IP etc)
 * Subentities should be extended from MAIN class
 * 
 * Details:
 *  - methods, used for ajax executions must be postfixed with execute keyword
 *      Eg.: getDnsInformationExecute()
 * 
 * @author vesta, http://vestacp.com/
 * @author Dmitry Malishev <dima.malishev@gmail.com>
 * @author Dmitry Naumov-Socolov <naumov.socolov@gmail.com>
 * @copyright vesta 2010-2011
 */
class MAIN extends AjaxHandler 
{

    protected $templates = null;

    /**
     * Get Version
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function versionExecute(Request $request) 
    {
        $result = array(
                    'version' => '1.0',
                    'author'  => 'http://vestacp.com/',
                    'docs'    => 'http://vestacp.com/docs'
                  );

        return $this->reply(true, $result);
    }

    public function signinExecute($request)
    {
	$login = $request->getParameter('login');
	$password = $request->getParameter('password');

	$result = Vesta::execute('v_check_sys_user_password', array('USER' => $login, 'PASSWORD' => $password));
	if ($result['status'] == true) {
	    return $this->reply(VestaSession::authorize($login));
	}
	else {
	    return $this->reply(false, 'Incorrect login / password');
	}
    }

    /**
     * Get Initial params.
     * Global constants / variables / configs
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function getInitialExecute(Request $request) 
    {
	$user = VestaSession::getInstance()->getUser();
	$global_data = array();
	$totals = array(
	    	    'USER'       => array('total' => 0, 'blocked' => 0),
                    'WEB_DOMAIN' => array('total' => 0, 'blocked' => 0),
	            'MAIL'       => array('total' => 0),
                    'DB'         => array('total' => 0, 'blocked' => 0),
                    'DNS'        => array('total' => 0, 'blocked' => 0),
                    'IP'         => array('total' => 0, 'blocked' => 0),
                    'CRON'       => array('total' => 0, 'blocked' => 0)                
                );
    
	// users
	$rs = Vesta::execute(Vesta::V_LIST_SYS_USERS, null, self::JSON);
	$data_user = $rs['data'];
	$global_data['users'] = array();
	foreach ($data_user as $login_name => $usr) {
	    $totals['USER']['total'] += 1;
	    if ($usr['SUSPENDED'] != 'yes') {		
		$global_data['users'][$login_name] = $login_name;
	    }
	    else {
		$totals['USER']['blocked'] += 1;
	    }
	}
	// web_domains
	$rs = Vesta::execute(Vesta::V_LIST_WEB_DOMAINS, array('USER' => $user['uid']), self::JSON);
	$data_web_domain = $rs['data'];
	foreach ($data_web_domain as $web) {
	    $totals['WEB_DOMAIN']['total'] += 1;
	}
	// db
	$rs = Vesta::execute(Vesta::V_LIST_DB_BASES, array('USER' => $user['uid']), self::JSON);
	$data_db = $rs['data'];
	foreach ($data_db as $db) {
	    $totals['DB']['total'] += 1;
	    //$db['SUSPENDED'] == 'yes' ? $totals['DB']['blocked'] += 1 : false;
	}
	// dns
	$rs = Vesta::execute(Vesta::V_LIST_DNS_DOMAINS, array('USER' => $user['uid']), self::JSON);
	$data_dns = $rs['data'];
	foreach ($data_dns as $dns) {
	    $totals['DNS']['total'] += 1;
	}
	// ip
	$global_data['ips'] = array();
	$rs = Vesta::execute(Vesta::V_LIST_SYS_IPS, null, self::JSON);
	$data_ip = $rs['data'];
	foreach ($data_ip as $ip => $obj) {
	    $totals['IP']['total'] += 1;
	    $global_data['ips'][$ip] = $ip;
	}
	// cron
	$rs = Vesta::execute(Vesta::V_LIST_CRON_JOBS, array('USER' => $user['uid']), self::JSON);
	$data_cron = $rs['data'];
	foreach ($data_cron as $cron) {
	    $totals['CRON']['total'] += 1;
	    $cron['SUSPEND'] == 'yes' ? $totals['CRON']['blocked'] += 1 : false;
	}

	$reply = array(
                    'WEB_DOMAIN' => $this->getWebDomainParams($data_web_domin, $global_data),
                    'CRON'       => $this->getCronParams(),
                    'IP'         => $this->getIpParams($data_ip, $global_data),
                    'DNS'        => $this->getDnsParams(),
                    'DB'         => $this->getDbParams($data_db),
                    'USERS'      => $this->getUsersParams($data_user),
                    'totals'     => $totals
                );

        return $this->reply(true, $reply);
    }

    protected function getTemplates()
    {
	if (null != $this->templates) {
	    return $this->templates;
	}
	else {
	    $user = $this->getLoggedUser();
	    $this->templates = array();
	    //v_list_web_templates vesta json
	    $result = Vesta::execute('v_list_web_templates', array('USER' => $user['uid']), self::JSON);
	    // TODO: handle errors!
	    foreach ($result['data'] as $tpl => $description) {
		$this->templates[$tpl] = $tpl;
	    }

	    return $this->templates;
	}
    }
    
    /**
     * WEB DOMAIN initial params
     * 
     * @params array $data
     * @return array
     */
    public function getWebDomainParams($data, $global_data)
    {
	$user = $this->getLoggedUser();
	$ips = array();
        $result	= Vesta::execute(Vesta::V_LIST_SYS_USER_IPS, array('USER' => $user['uid']), self::JSON);
	foreach ($result['data'] as $sys_ip => $ip_data) {
	    $ips[$sys_ip] = $sys_ip;
	}

	if (empty($ips)) {
	    $ips['No available IP'] = 'No available IP';
	}

        return array(
                'TPL' => $this->getTemplates(),
                'ALIAS' => array(),
                'STAT' => array(
			    'none'      => 'none',
                            'webalizer' => 'webalizer',
                            'awstats'   => 'awstats'
                          ),
                'IP' => $ips
           );
    }
    
    /**
     * CRON initial params
     * 
     * @params array $data
     * @return array
     */
    public function getCronParams($data = array())
    {
        return array();
    }
    
    /**
     * IP initial params
     * 
     * @params array $data
     * @return array
     */
    public function getIpParams($data = array(), $global_data = array())
    {
	$ifaces  = array();                                                                                                                                                                                                            
        $result = Vesta::execute(Vesta::V_LIST_SYS_INTERFACES, array(Config::get('response_type')));                                                                                                                                  
                                                                                                                                                                                                                                      
        foreach ($result['data'] as $iface) {                                                                                                                                                                                         
            $ifaces[$iface] = $iface;                                                                                                                                                                                                  
        }                
	
        return array(
                'SYS_USERS' => $users,
                'STATUSES' => array(
                                'shared' => 'shared',
                                'exclusive' => 'exclusive'
                              ),
                'INTERFACES' => $ifaces,
		'OWNER' => $global_data['users'],
                'MASK' => array(
                            '255.255.255.0' => '255.255.255.0',
                            '255.255.255.128' => '255.255.255.128',
                            '255.255.255.192' => '255.255.255.192',
                            '255.255.255.224' => '255.255.255.224', 
                            '255.255.255.240' => '255.255.255.240', 
                            '255.255.255.248' => '255.255.255.248',
                            '255.255.255.252' => '255.255.255.252',
                            '255.255.255.255' => '255.255.255.255'
                          )
            );
    }
    
    /**
     * DNS initial params
     * 
     * @params array $data
     * @return array
     */
    public function getDnsParams($data = array())
    {
        return  array(
                'IP' => @$data['ips'],
                'TPL' => $this->getTemplates(),
                'EXP' => array(),
                'SOA' => array(),
                'TTL' => array(),
                'record' => array(
                                'RECORD' => array(),
                                'RECORD_TYPE' => array('A' => 'A', 'NS' => 'NS', 'MX' => 'MX', 'TXT' => 'TXT'),
                                'RECORD_VALUE' => array()
                            )
            );
    }
    
    /**
     * DB initial params
     * 
     * @params array $data
     * @return array
     */
    public function getDbParams($data = array())
    {
        $db_types = $this->getDBTypes();
        return array(
                    'TYPE' => $db_types,
                    'HOST' => array('vestacp.com' => 'vestacp.com', 'askcow.org' => 'askcow.org')
                );
    }
    
    public function getDBTypes()
    {
        return array('mysql' => 'mysql', 'postgre' => 'postgre');
    }
    
    /**
     * Users initial params
     * 
     * @params array $data
     * @return array
     */
    public function getUsersParams($data = array())
    {
	$pckg = array();
	// json
	$result = Vesta::execute('v_list_sys_user_packages', null, self::JSON);
	foreach ($result['data'] as $pckg_name => $pckg_data) {
	    $pckg[$pckg_name] = $pckg_name;
	}
        return array(
                'ROLE'      => array('user' => 'user'),
                'OWNER'     => $data['user_names'],
                'PACKAGE'   => $pckg,
                'SHELL'     => array(
                                'sh'       => 'sh',
                                'bash'     => 'bash',
                                'nologin'  => 'nologin',
                                'tcsh'     => 'tcsh',
                                'csh'      => 'csh')
                );
    }
        
}
