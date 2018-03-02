<?php

use Phergie\Irc\Connection;

return array(

    // Plugins to include for all connections

    'plugins' => array(

        // new \Vendor\Plugin\PluginName(array(
        // /* configuration goes here */
        // )),

    ),

    'connections' => array(

        new Connection(array(

            // Required settings

            'serverHostname' => 'irc.freenode.net',
            'username' => 'IRC-bot',
            'realname' => 'IRC-bot',
            'nickname' => 'IRC-bot',

            // Optional settings

            // 'hostname' => 'user server name goes here if needed',
            // 'serverport' => 6667,
            // 'password' => 'password goes here if needed',
            // 'options' => array(
            //     'transport' => 'ssl',
            //     'force-ipv4' => true,
            // )

        )),

    )

);
