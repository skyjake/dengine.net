<?php
/*
 * Doomsday Web API: Master Server
 * Copyright (c) 2016-2017 Jaakko Keränen <jaakko.keranen@iki.fi>
 *
 * License: GPL v2+
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version. This program is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details. You should have received a copy of the GNU
 * General Public License along with this program; if not, see:
 * http://www.gnu.org/licenses/gpl.html
 */

/*
 * The Master Server has the following responsibilities:
 *
 * - Receive server announcements (in JSON via HTTP POST), parse them, and store
 *   them in a database.
 * - Answer HTTP GET queries about which servers are currently running
 *   (also JSON).
 * - Remove expired entries from the database.
 */

require_once('include/database.inc.php');

define('DB_TABLE', 'servers');
define('EXPIRE_SECONDS', 900);
define('DEFAULT_PORT', 13209);

// Initializes the Servers database table.
function db_init()
{
    $table = DB_TABLE;

    $db = db_open();
    db_query($db, "DROP TABLE IF EXISTS $table");

    $sql = "CREATE TABLE $table ("
         . "timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP "
            ."ON UPDATE CURRENT_TIMESTAMP, "
         . "address INT UNSIGNED NOT NULL, "
         . "port SMALLINT UNSIGNED NOT NULL, "
         . "domain VARCHAR(100), "
         . "server_id INT UNSIGNED, "
         . "name VARCHAR(100) NOT NULL, "
         . "description VARCHAR(500), "
         . "version VARCHAR(30) NOT NULL, "
         . "compat INT NOT NULL, "
         . "plugin VARCHAR(50), "
         . "packages TEXT, "
         . "game_id VARCHAR(50) NOT NULL, "
         . "game_config VARCHAR(200), "
         . "map VARCHAR(50), "
         . "player_count SMALLINT, "
         . "player_max SMALLINT NOT NULL, "
         . "player_names TEXT, "
         . "flags INT UNSIGNED NOT NULL, "
         . "PRIMARY KEY (address, port)) CHARACTER SET utf8";

    db_query($db, $sql);
    $db->close();
}

function is_valid_host($ip)
{
    if (($ip & 0xff000000) == 0x7f000000) return false; // No loopback.
    if (($ip & 0xff) == 0xff) return false; // Broadcast address.
    return true;
}

function parse_announcement($json_data)
{
    $server_info = json_decode($json_data);
    if ($server_info == NULL) return; // JSON parse error.

    $address = ip2long($_SERVER['REMOTE_ADDR']);

    if (!is_valid_host($address)) {
        echo 'Remote host has an invalid address';
        exit;
    }

    if (property_exists($server_info, 'dom')) {
        $domain = urlencode($server_info->dom);
    }
    else {
        $domain = '';
    }
    if (property_exists($server_info, 'sid')) {
        $sid = (int) $server_info->sid;
    }
    else {
        $sid = 0;
    }
    $port         = (int) $server_info->port;
    $name         = urlencode($server_info->name);
    $description  = urlencode($server_info->desc);
    $version      = urlencode($server_info->ver);
    $compat       = (int) $server_info->cver;
    $plugin       = urlencode($server_info->plugin);
    $packages     = urlencode(json_encode($server_info->pkgs));
    $game_id      = urlencode($server_info->game);
    $game_config  = urlencode($server_info->cfg);
    $map          = urlencode($server_info->map);
    $player_count = property_exists($server_info, 'pnum')? ((int) $server_info->pnum) : 0;
    $player_max   = (int) $server_info->pmax;
    $player_names = urlencode(json_encode($server_info->plrs));
    $flags        = (int) $server_info->flags;

    if ($port == 0) $port = DEFAULT_PORT;

    $db = db_open();
    $table = DB_TABLE;
    db_query($db, "DELETE FROM $table WHERE address = $address AND port = $port");
    db_query($db, "INSERT INTO $table (address, port, domain, server_id, name, description, version, compat, plugin, packages, game_id, game_config, map, player_count, player_max, player_names, flags) "
        . "VALUES ($address, $port, '$domain', $sid, '$name', '$description', '$version', $compat, '$plugin', '$packages', '$game_id', '$game_config', '$map', $player_count, $player_max, '$player_names', $flags)");
    $db->close();
}

function fetch_servers()
{
    $servers = [];

    $db = db_open();
    $table = DB_TABLE;

    // Expire old announcements.
    $expire_ts = time() - EXPIRE_SECONDS;
    db_query($db, "DELETE FROM $table WHERE UNIX_TIMESTAMP(timestamp) < $expire_ts");

    // Get all the remaining servers.
    $result = db_query($db, "SELECT * FROM $table");
    while ($row = $result->fetch_assoc()) {
        $sv = array(
            "__obj__" => "Record",
            "host"    => long2ip($row['address']),
            "port"    => (int) $row['port'],
            "dom"     => urldecode($row['domain']),
            "sid"     => (int) $row['server_id'],
            "name"    => urldecode($row['name']),
            "desc"    => urldecode($row['description']),
            "ver"     => urldecode($row['version']),
            "cver"    => (int) $row['compat'],
            "plugin"  => urldecode($row['plugin']),
            "pkgs"    => json_decode(urldecode($row['packages'])),
            "game"    => urldecode($row['game_id']),
            "cfg"     => urldecode($row['game_config']),
            "map"     => urldecode($row['map']),
            "pnum"    => (int) $row['player_count'],
            "pmax"    => (int) $row['player_max'],
            "plrs"    => json_decode(urldecode($row['player_names'])),
            "flags"   => (int) $row['flags']
        );
        $servers[] = $sv;
    }
    $db->close();

    return $servers;
}

//---------------------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // GET requests are for querying information about servers.
    $op = $_GET['op'];
    if ($op == 'list') {
        $servers = fetch_servers();
        echo json_encode($servers);
    }
    else if (DENG_SETUP_ENABLED && $op == 'setup') {
        echo "Initializing database...";
        db_init();
    }
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // POST requests are for submitting info about running servers.
    parse_announcement(file_get_contents("php://input"));
}
