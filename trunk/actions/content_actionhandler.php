<?php 

function subtreelocation_ContentActionHandler( &$module, &$http, &$objectID )
{
    if ( $http->hasPostVariable( 'AddSubtreeAssignmentButton' ) )
    {
        $viewMode = 'full';
        $languageCode = false;
        
        if ($module->currentAction() != 'AddSubtreeAssignment') {
            $module->setCurrentAction('SelectSubtreeAssignmentLocation');
        }
        
        if ( !$http->hasPostVariable( 'ContentNodeID' ) ) {
            eZDebug::writeError( "Missing NodeID parameter for action " . $module->currentAction(),
                                     'content/action' );
            return $module->redirectToView( 'view', array( 'full', 2 ) );
        }
        $nodeID = $http->variable('ContentNodeID');

        if ( !$http->hasPostVariable( 'ContentObjectID' ) ) {
            eZDebug::writeError( "Missing ObjectID parameter for action " . $module->currentAction(),
                                     'content/action' );
            return $module->redirectToView( 'view', array( 'full', 2 ) );
        }
        $objectID = $http->variable('ContentObjectID');
        
        if ( $module->isCurrentAction( 'AddSubtreeAssignment' ) or
          $module->isCurrentAction( 'SelectSubtreeAssignmentLocation' ) )
        {
            $object = eZContentObject::fetch( $objectID );
            if ( !$object )
            {
                return $module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
            }

            $user = eZUser::currentUser();
            if ( !$object->checkAccess( 'edit' ) &&
                 !$user->attribute( 'has_manage_locations' ) )
            {
                return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
            }

            $existingNode = eZContentObjectTreeNode::fetch( $nodeID );
            if ( !$existingNode )
            {
                return $module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
            }

            $class = $object->contentClass();
            if ( $module->isCurrentAction( 'AddSubtreeAssignment' ) )
            {
                $selectedNodeIDArray = '';
                if ( !is_array( $selectedNodeIDArray ) )
                    $selectedNodeIDArray = array();

                $selectedNodeIDArray = eZContentBrowse::result( 'AddSubtreeAssignment' );
                if (count($selectedNodeIDArray))
                {
                    $operation = subtreeLocation::addAssignment( $nodeID, $objectID, $selectedNodeIDArray );
                    if ( !$operation['status'] )
                        return $module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );
                }
                return $module->redirectToView( 'view', array( 'full', $nodeID ) );
            }
            elseif ( $module->isCurrentAction( 'SelectSubtreeAssignmentLocation' ) )
            {
                $ignoreNodesSelect = array();
                $ignoreNodesClick  = array();
                
                $assigned = eZNodeAssignment::fetchForObject( $objectID, $object->attribute( 'current_version' ), 0, false );
                $publishedAssigned = $object->assignedNodes( false );
                $isTopLevel = false;
                foreach ( $publishedAssigned as $element )
                {
                    $append = false;
                    if ( $element['parent_node_id'] == 1 )
                        $isTopLevel = true;
                    foreach ( $assigned as $ass )
                    {
                        if ( $ass['parent_node'] == $element['parent_node_id'] )
                        {
                            $append = true;
                            break;
                        }
                    }
                    if ( $append )
                    {
                        $ignoreNodesSelect[] = $element['node_id'];
                        $ignoreNodesClick[]  = $element['node_id'];
                        $ignoreNodesSelect[] = $element['parent_node_id'];
                    }
                }
            
                if ( !$isTopLevel )
                {
                    $ignoreNodesSelect = array_unique( $ignoreNodesSelect );
                    $objectID = $object->attribute( 'id' );
                    eZContentBrowse::browse( array( 'action_name' => 'AddSubtreeAssignment',
                                                    'description_template' => 'design:content/browse_subtreeplacement.tpl',
                                                    'keys' => array( 'class' => $class->attribute( 'id' ),
                                                                     'class_id' => $class->attribute( 'identifier' ),
                                                                     'classgroup' => $class->attribute( 'ingroup_id_list' ),
                                                                     'section' => $object->attribute( 'section_id' ) ),
                                                    'ignore_nodes_select' => $ignoreNodesSelect,
                                                    'ignore_nodes_click'  => $ignoreNodesClick,
                                                    'persistent_data' => array( 'ContentNodeID' => $nodeID,
                                                                                'ContentObjectID' => $objectID,
                                                                                'ViewMode' => $viewMode,
                                                                                'ContentObjectLanguageCode' => $languageCode,
                                                                                'AddSubtreeAssignmentButton' => '1' ),
                                                    'content' => array( 'object_id' => $objectID,
                                                                        'object_version' => $object->attribute( 'current_version' ),
                                                                        'object_language' => $languageCode ),
                                                    'cancel_page' => $module->redirectionURIForModule( $module, 'view', array( $viewMode, $nodeID, $languageCode ) ),
                                                    'from_page' => "/content/action" ),
                                            $module );

                    return true;
                }
            
                return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
            }
        }
    }
    
    return true;
}


