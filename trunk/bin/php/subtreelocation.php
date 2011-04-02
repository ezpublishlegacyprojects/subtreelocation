#!/usr/bin/env php
<?php

// Subtree Location Script
// file  extension/subtreelocation/bin/php/ezsubtreecopy.php

// script initializing
require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "\n" .
                                                         "This script will add a location at a whole subtree.\n" ),
                                      'use-session' => false,
                                      'use-modules' => true,
                                      'use-extensions' => true,
                                      'user' => true ) );
$script->startup();

$scriptOptions = $script->getOptions( "[src-node-id:][loc-node-id:][login:]",
                                      "",
                                      array( 'src-node-id' => "Source subtree root node ID.",
                                             'loc-node-id' => "Location node ID.",
                                             'login'       => "Username from user with location perms in content section"
                                             ),
                                      false,
                                      array( 'user' => true )
                                     );
                                     
$script->initialize();

// Manage user perms
$login = $scriptOptions[ 'login' ] ? $scriptOptions[ 'login' ] : false;
if (!$login)
{
  $subtreeINI = eZINI::instance('subtreelocation.ini');
  $login = $subtreeINI->variable('SubtreeLocationScript', 'UserName');
}
$user = eZUser::fetchByName( $login );
$userID = $user->attribute( 'contentobject_id' );
eZUser::setCurrentlyLoggedInUser( $user, $userID );

// Manage location params
$srcNodeID   = $scriptOptions[ 'src-node-id' ] ? $scriptOptions[ 'src-node-id' ] : false;
$locNodeID   = $scriptOptions[ 'loc-node-id' ] ? $scriptOptions[ 'loc-node-id' ] : false;

$sourceSubTreeMainNode = ( $srcNodeID ) ? eZContentObjectTreeNode::fetch( $srcNodeID ) : false;
$newLocationNode = ( $locNodeID ) ? eZContentObjectTreeNode::fetch( $locNodeID ) : false;

if ( !$sourceSubTreeMainNode )
{
    $cli->error( "Subtree location Error!\nCannot get subtree main node. Please check src-node-id argument and try again." );
    $script->showHelp();
    $script->shutdown( 1 );
}
if ( !$newLocationNode )
{
    $cli->error( "Subtree location Error!\nCannot get new location node. Please check loc-node-id argument and try again." );
    $script->showHelp();
    $script->shutdown( 1 );
}

$objectID            = $sourceSubTreeMainNode->object()->ID;
$selectedNodeIDArray = array($locNodeID);

// add subtree location
$operation = subtreeLocation::addAssignment( $srcNodeID, $objectID, $selectedNodeIDArray );
if ( !$operation['status'] )
{
    $cli->error( "Subtree location Error!\nCannot add the location to the subtree. Check permissions, database, etc. and try again." );
    $script->showHelp();
    $script->shutdown( 1 );
}

$cli->output( "Done." );

$script->shutdown();

?>
