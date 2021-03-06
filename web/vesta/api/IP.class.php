<?php
/**
 * IP 
 * 
 * @author vesta, http://vestacp.com/
 * @author Dmitry Malishev <dima.malishev@gmail.com>
 * @author Dmitry Naumov-Socolov <naumov.socolov@gmail.com>
 * @copyright vesta 2010-2011
 */
 
class IP extends AjaxHandler
{
    
    /**
     * Get IP entries
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function getListExecute(Request $request) 
    {
        $reply  = array();
        $result = Vesta::execute(Vesta::V_LIST_SYS_IPS, array(Config::get('response_type')));
        foreach ($result['data'] as $ip => $details) {
            $reply[] = array_merge(
                            array(
                                'IP_ADDRESS' => $ip,
                                'DATE'       => date(Config::get('ui_date_format', strtotime($details['DATE'])))
                            ), $details);
        }
    
        if (!$result['status']) {
            $this->errors[] = array($result['error_code'] => $result['error_message']);
        }
    
        return $this->reply($result['status'], $reply);
    }

    /**
     * Get user's IPs
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function getListUserIpsExecute(Request $request) 
    {
        $reply  = array();
        $result = Vesta::execute(Vesta::V_LIST_SYS_IPS, array(Config::get('response_type')));
        foreach ($result['data'] as $ip => $details) {
            $reply[] = array_merge(
                            array(
                                'IP_ADDRESS' => $ip,
                                'DATE'       => date(Config::get('ui_date_format', strtotime($details['DATE'])))
                            ), $details);
        }
    
        if (!$result['status']) {
            $this->errors[] = array($result['error_code'] => $result['error_message']);
        }
    
        return $this->reply($result['status'], $reply);
    }
    
    /**
     * Add IP entry
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function addExecute(Request $request) 
    {
        $user   = $this->getLoggedUser();
        $spell  = $request->getParameter('spell');      
        $params = array(
                      'IP_ADDRESS' => $spell['IP_ADDRESS'],
                      'MASK'        => $spell['NETMASK'],
                      'INTERFACE'  => $spell['INTERFACE'],
                      'OWNER'      => $spell['OWNER'],
                      'IP_STATUS'  => $spell['STATUS']                      
                  );
      
        $result = Vesta::execute(Vesta::V_ADD_SYS_IP, $params);
      
        if (!$result['status']) {
            $this->errors[] = array($result['error_code'] => $result['error_message']);
        }
      
        return $this->reply($result['status'], $result['data']);
    }

    /**
     * Delete IP entry
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function deleteExecute(Request $request) 
    {
        $spell  = $request->getParameter('spell');
        $user   = $this->getLoggedUser(); 
        $params = array(
                    'IP_ADDRESS' => $spell['IP_ADDRESS']
                  );
      
        $result = Vesta::execute(Vesta::V_DEL_SYS_IP, $params);
      
        if (!$result['status']) {
            $this->errors[] = array($result['error_code'] => $result['error_message']);
        }
      
        return $this->reply($result['status'], $result['data']);
    }

    /**
     * Change IP entry
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function changeExecute(Request $request)
    {
        $user   = $this->getLoggedUser(); 
        $_old   = $request->getParameter('old');
        $_new   = $request->getParameter('new');
   
        $this->status = TRUE;
        $this->errors = array();

        if ($_old['OWNER'] != $_new['OWNER']) {
            $result = array();
            $result = Vesta::execute(Vesta::V_CHANGE_SYS_IP_OWNER, array('OWNER' => $_new['OWNER'], 'IP' => $_new['IP_ADDRESS']));
            if (!$result['status']) {
                $this->status = FALSE;
                $this->errors['OWNER'] = array($result['error_code'] => $result['error_message']);
            }
        }

        // TODO: Handle NAME parameter
        /*if ($_old['NAME'] != $_new['NAME']) {
            $result = array();
            $result = Vesta::execute(Vesta::V_CHANGE_SYS_IP_NAME, array('IP' => $_new['IP_ADDRESS'], 'NAME' => $_new['NAME']));
            if (!$result['status']) {
                $this->status = FALSE;
                $this->errors['NAME'] = array($result['error_code'] => $result['error_message']);
            }
        }*/

        if ($_old['IP_STATUS'] != $_new['IP_STATUS']) {
            $result = array();
            $result = Vesta::execute(Vesta::V_CHANGE_SYS_IP_STATUS, array('IP' => $_new['IP_ADDRESS'], 'IP_STATUS' => $_new['IP_STATUS']));
            if (!$result['status']) {
                $this->status = FALSE;
                $this->errors['IP_STATUS'] = array($result['error_code'] => $result['error_message']);
            }
        }

        if (!$result['status']) {
            $this->errors[] = array($result['error_code'] => $result['error_message']);
        }
      
        return $this->reply($this->status, $this->errors);
    }

    /**
     * Get Sys interfaces
     * 
     * @param Request $request
     * @return string - Ajax Reply
     */ 
    public function getSysInterfacesExecute(Request $request) 
    {
        $reply  = array();
        $result = Vesta::execute(Vesta::V_LIST_SYS_INTERFACES, array(Config::get('response_type')));

        foreach ($result['data'] as $iface) {
            $reply[$iface] = $iface;
        }
    
        if (!$result['status']) {
            $this->errors[] = array($result['error_code'] => $result['error_message']);
        }
    
        return $this->reply($result['status'], $reply);
    }
    
}
