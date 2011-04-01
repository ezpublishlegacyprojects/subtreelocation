<?php 

class subtreeLocation
{
    static function addAssignment( $nodeID, $objectID, $selectedNodeIDArray )
    {
        $nodeToPlaceArray = array();
        $rootNode = eZContentObjectTreeNode::fetch($nodeID);
        if ( !$rootNode )
            return array( 'status' => false );
        
        $nodeToPlaceArray[] = $rootNode; 
        $rootNodeChildren = $rootNode->subTree();
        $nodeToPlaceArray = array_merge($nodeToPlaceArray, $rootNodeChildren);
        
        foreach ($selectedNodeIDArray as $selectedNodeID)
        {
            $selectedNode = eZContentObjectTreeNode::fetch($selectedNodeID);
            if ( !$selectedNode )
                return array( 'status' => false );
            
            foreach ($nodeToPlaceArray as $i => $nodeToPlace)
            {
                if ($i == 0)
                {
                    $tmpSelectedNodeIDArray = array($selectedNodeID);
                    $operation = eZContentOperationCollection::addAssignment( $nodeToPlace->NodeID, $nodeToPlace->ContentObjectID, $tmpSelectedNodeIDArray );
                    if ( !$operation['status'] )
                        return $operation;
                }
                else
                {
                    $parentNodeToPlace = $nodeToPlace->fetchParent();
                    $parentObjectNodeToPlace = $parentNodeToPlace->object();
                    $parentNodeToPlaceAssignmentArray = $parentObjectNodeToPlace->assignedNodes();
                    foreach ($parentNodeToPlaceAssignmentArray as $parentNodeToPlaceAssignment) {
                        $isLocation = strpos($parentNodeToPlaceAssignment->PathString,'/'.$selectedNodeID.'/');
                        $tmpSelectedNodeIDArray = array($parentNodeToPlaceAssignment->NodeID);
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

