<?php 

class subtreeLocation
{
    static function addAssignment( $nodeID, $objectID, $selectedNodeIDArray )
    {
        // Merge all node to place in an array
        $nodeToPlaceArray = array();
        $rootNode = eZContentObjectTreeNode::fetch($nodeID);
        if ( !$rootNode )
            return array( 'status' => false );
        
        $nodeToPlaceArray[] = $rootNode; 
        $rootNodeChildren = $rootNode->subTree();
        $nodeToPlaceArray = array_merge($nodeToPlaceArray, $rootNodeChildren);
        
        // Operation for each selected node
        foreach ($selectedNodeIDArray as $selectedNodeID)
        {
            $selectedNode = eZContentObjectTreeNode::fetch($selectedNodeID);
            if ( !$selectedNode )
                return array( 'status' => false );
            
            // Operation for each node to locate
            foreach ($nodeToPlaceArray as $i => $nodeToPlace)
            {
                // Manage the root node of the subtree
                if ($i == 0)
                {
                    $tmpSelectedNodeIDArray = array($selectedNodeID);
                    $operation = eZContentOperationCollection::addAssignment( $nodeToPlace->NodeID, $nodeToPlace->ContentObjectID, $tmpSelectedNodeIDArray );
                    if ( !$operation['status'] )
                        return $operation;
                }
                else
                {
                    // Manage the other nodes
                    $parentNodeToPlace = $nodeToPlace->fetchParent();
                    $parentObjectNodeToPlace = $parentNodeToPlace->object();
                    $parentNodeToPlaceAssignmentArray = $parentObjectNodeToPlace->assignedNodes();
                    foreach ($parentNodeToPlaceAssignmentArray as $parentNodeToPlaceAssignment) {
                        $isLocation = strpos($parentNodeToPlaceAssignment->PathString,'/'.$selectedNodeID.'/');
                        $tmpSelectedNodeIDArray = array($parentNodeToPlaceAssignment->NodeID);
                        // If the parent node is already located and this location contains the selected node
                        // Add location to the current node to locate
                        if ($isLocation !== false)
                        {
                            $operation = eZContentOperationCollection::addAssignment( $nodeToPlace->NodeID, $nodeToPlace->ContentObjectID, $tmpSelectedNodeIDArray );
                            if ( !$operation['status'] )
                                return $operation;
                            break;
                        }
                    }
                }
            } 
        }
        return array( 'status' => true );
    }
}

