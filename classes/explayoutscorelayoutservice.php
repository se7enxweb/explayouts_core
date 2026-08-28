<?php

class expLayoutsCoreLayoutService
{
    public function load( $id, $status = null )
    {
        return expLayoutsLayout::fetch( (int)$id );
    }

    public function loadDraft( $id )
    {
        $layout = $this->load( (int)$id );
        if ( !$layout )
            return false;

        if ( (int)$layout->attribute( 'status' ) === 1 )
            return $layout;

        return expLayoutsLayout::fetchByIdentifier( (string)$layout->attribute( 'identifier' ), 1 );
    }

    public function loadPublished( $id )
    {
        $layout = $this->load( (int)$id );
        if ( !$layout )
            return false;

        if ( (int)$layout->attribute( 'status' ) === 2 )
            return $layout;

        return expLayoutsLayout::fetchByIdentifier( (string)$layout->attribute( 'identifier' ), 2 );
    }

    public function listAll( $status = null )
    {
        if ( $status !== null )
            return expLayoutsLayout::fetchList( $status );

        $all = expLayoutsLayout::fetchList();
        $byIdentifier = array();
        foreach ( $all as $layout )
        {
            $identifier = (string)$layout->attribute( 'identifier' );
            $layoutStatus = (int)$layout->attribute( 'status' );
            if ( !isset( $byIdentifier[$identifier] ) || $layoutStatus === 2 )
                $byIdentifier[$identifier] = $layout;
        }

        return array_values( $byIdentifier );
    }

    public function create( $identifier, $name = '', $layoutType = '' )
    {
        $layout = expLayoutsLayout::create( trim( $identifier ) );
        $layout->setAttribute( 'name', trim( $name ) );
        $layout->setAttribute( 'layout_type', trim( $layoutType ) );
        $layout->setAttribute( 'created', time() );
        $layout->setAttribute( 'modified', time() );
        $layout->store();

        $zones = expLayoutsLayoutType::getZones( trim( $layoutType ) );
        $position = 0;
        foreach ( $zones as $zoneIdentifier )
        {
            $zone = expLayoutsZone::create( (int)$layout->attribute( 'id' ), $zoneIdentifier, (int)$layout->attribute( 'status' ) );
            $zone->setAttribute( 'position', $position++ );
            $zone->store();
        }

        return $layout;
    }

    public function update( $id, $attributes )
    {
        $layout = $this->load( (int)$id );
        if ( !$layout )
            return false;

        foreach ( $attributes as $key => $value )
        {
            if ( in_array( $key, array( 'identifier', 'name', 'layout_type', 'status' ) ) )
                $layout->setAttribute( $key, $value );
        }
        $layout->setAttribute( 'modified', time() );
        $layout->store();
        return $layout;
    }

    public function publish( $id )
    {
        $draft = $this->load( (int)$id );
        if ( !$draft )
            return false;

        $identifier = (string)$draft->attribute( 'identifier' );
        $published = expLayoutsLayout::fetchByIdentifier( $identifier, 2 );

        if ( !$published )
        {
            $draft->publish();
            return $this->loadPublished( (int)$draft->attribute( 'id' ) );
        }

        $published->setAttribute( 'name', (string)$draft->attribute( 'name' ) );
        $published->setAttribute( 'layout_type', (string)$draft->attribute( 'layout_type' ) );
        $published->setAttribute( 'status', 2 );
        $published->setAttribute( 'modified', time() );
        $published->store();

        $this->clearLayoutContent( $published );
        $this->copyLayoutContent( $draft, $published );

        $this->delete( (int)$draft->attribute( 'id' ) );

        return $published;
    }

    public function createDraft( $id )
    {
        $published = $this->load( (int)$id );
        if ( !$published )
            return false;

        $identifier = (string)$published->attribute( 'identifier' );

        if ( (int)$published->attribute( 'status' ) === 1 )
            return $published;

        $draft = expLayoutsLayout::fetchByIdentifier( $identifier, 1 );
        if ( $draft )
            return $draft;

        $draft = expLayoutsLayout::create( $identifier, (string)$published->attribute( 'name' ), (string)$published->attribute( 'layout_type' ) );
        $draft->setAttribute( 'status', 1 );
        $draft->setAttribute( 'created', (int)$published->attribute( 'created' ) );
        $draft->setAttribute( 'modified', time() );
        $draft->store();

        $this->copyLayoutContent( $published, $draft );

        return $draft;
    }

    public function discard( $id )
    {
        $draft = $this->loadDraft( (int)$id );
        if ( !$draft )
            return false;

        if ( (int)$draft->attribute( 'status' ) !== 1 )
            return $this->loadPublished( (int)$draft->attribute( 'id' ) );

        $identifier = (string)$draft->attribute( 'identifier' );
        $published = $this->loadByIdentifier( $identifier, 2 );

        if ( !$published )
            return $draft;

        $this->delete( (int)$draft->attribute( 'id' ) );
        return $published;
    }

    public function copy( $id )
    {
        $source = $this->load( (int)$id );
        if ( !$source )
            return false;

        $newIdentifier = trim( $source->attribute( 'identifier' ) ) . '_' . time();
        $copy = $this->create(
            $newIdentifier,
            (string)$source->attribute( 'name' ) . ' (copy)',
            (string)$source->attribute( 'layout_type' )
        );

        $this->copyLayoutContent( $source, $copy );

        return $copy;
    }

    public function delete( $id )
    {
        $layout = $this->load( (int)$id );
        if ( !$layout )
            return false;

        // Disable any rules tied to this layout so a removed layout does not
        // leave the resolver matching a path to a non-existent layout.
        eZDB::instance()->query( 'UPDATE explayouts_rule SET enabled = 0 WHERE layout_id = ' . (int)$id );

        $this->clearLayoutContent( $layout );
        $layout->remove();
        return true;
    }

    public function loadByIdentifier( $identifier, $status = 2 )
    {
        return expLayoutsLayout::fetchByIdentifier( trim( $identifier ), (int)$status );
    }

    public function listByType( $layoutType )
    {
        return eZPersistentObject::fetchObjectList(
            expLayoutsLayout::definition(),
            null,
            array( 'layout_type' => trim( $layoutType ) ),
            array( 'modified' => 'desc', 'id' => 'desc' ),
            null, true
        );
    }

    protected function clearLayoutContent( $layout )
    {
        $layoutId = (int)$layout->attribute( 'id' );
        $status = (int)$layout->attribute( 'status' );

        $zones = expLayoutsZone::fetchByLayout( $layoutId, $status );
        foreach ( $zones as $zone )
        {
            $blocks = expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), $status );
            foreach ( $blocks as $block )
            {
                $this->clearBlockData( (int)$block->attribute( 'id' ) );
                $block->remove();
            }
            $zone->remove();
        }
    }

    protected function clearBlockData( $blockId )
    {
        $params = expLayoutsBlockParameter::fetchByBlock( $blockId );
        foreach ( $params as $param )
            $param->remove();

        $collection = expLayoutsCollection::fetchByBlock( $blockId );
        if ( $collection )
        {
            $query = expLayoutsCollectionQuery::fetchByCollection( (int)$collection->attribute( 'id' ) );
            if ( $query )
                $query->remove();

            $items = expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) );
            foreach ( $items as $item )
                $item->remove();

            $collection->remove();
        }
    }

    protected function copyLayoutContent( $source, $target )
    {
        $sourceId = (int)$source->attribute( 'id' );
        $sourceStatus = (int)$source->attribute( 'status' );
        $targetId = (int)$target->attribute( 'id' );
        $targetStatus = (int)$target->attribute( 'status' );

        $zones = expLayoutsZone::fetchByLayout( $sourceId, $sourceStatus );
        foreach ( $zones as $zone )
        {
            $newZone = expLayoutsZone::create( $targetId, (string)$zone->attribute( 'identifier' ), $targetStatus );
            $newZone->setAttribute( 'position', (int)$zone->attribute( 'position' ) );

            $linkedLayoutId = $zone->attribute( 'linked_layout_id' );
            if ( $linkedLayoutId !== null && $linkedLayoutId !== false && (int)$linkedLayoutId > 0 )
                $newZone->setAttribute( 'linked_layout_id', (int)$linkedLayoutId );

            $newZone->store();

            $blocks = expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), $sourceStatus );
            $blockIdMap = array();
            foreach ( $blocks as $block )
            {
                if ( (int)$block->attribute( 'parent_id' ) > 0 )
                    continue;

                $this->copyBlockTree( $block, $newZone, $targetStatus, $blockIdMap );
            }
        }
    }

    protected function copyBlockTree( $sourceBlock, $targetZone, $targetStatus, &$blockIdMap )
    {
        $newBlock = $this->createBlockCopy( $sourceBlock, $targetZone, $targetStatus, $blockIdMap );

        $children = expLayoutsBlock::fetchChildren( (int)$sourceBlock->attribute( 'id' ), (int)$sourceBlock->attribute( 'status' ) );
        foreach ( $children as $child )
        {
            $this->copyBlockTree( $child, $targetZone, $targetStatus, $blockIdMap );
        }

        return $newBlock;
    }

    protected function createBlockCopy( $sourceBlock, $targetZone, $targetStatus, &$blockIdMap )
    {
        $sourceBlockId = (int)$sourceBlock->attribute( 'id' );
        $newParentId = 0;

        $sourceParentId = (int)$sourceBlock->attribute( 'parent_id' );
        if ( $sourceParentId > 0 && isset( $blockIdMap[$sourceParentId] ) )
            $newParentId = $blockIdMap[$sourceParentId];

        $newBlock = expLayoutsBlock::create(
            (int)$targetZone->attribute( 'id' ),
            (int)$targetZone->attribute( 'layout_id' ),
            (string)$sourceBlock->attribute( 'definition_identifier' ),
            (string)$sourceBlock->attribute( 'name' )
        );
        $newBlock->setAttribute( 'view_type', (string)$sourceBlock->attribute( 'view_type' ) );
        $newBlock->setAttribute( 'item_view_type', (string)$sourceBlock->attribute( 'item_view_type' ) );
        $newBlock->setAttribute( 'position', (int)$sourceBlock->attribute( 'position' ) );
        $newBlock->setAttribute( 'parent_id', $newParentId );
        $newBlock->setAttribute( 'placeholder', (string)$sourceBlock->attribute( 'placeholder' ) );
        $newBlock->setAttribute( 'status', $targetStatus );
        $newBlock->store();

        $newBlockId = (int)$newBlock->attribute( 'id' );
        $blockIdMap[$sourceBlockId] = $newBlockId;

        $this->copyBlockData( $sourceBlock, $newBlock );

        return $newBlock;
    }

    protected function copyBlockData( $sourceBlock, $targetBlock )
    {
        $params = expLayoutsBlockParameter::fetchByBlock( (int)$sourceBlock->attribute( 'id' ) );
        foreach ( $params as $param )
        {
            expLayoutsBlockParameter::set( (int)$targetBlock->attribute( 'id' ), (string)$param->attribute( 'name' ), (string)$param->attribute( 'value' ) );
        }

        $collection = expLayoutsCollection::fetchByBlock( (int)$sourceBlock->attribute( 'id' ) );
        if ( $collection )
        {
            $newCollection = expLayoutsCollection::create( (int)$targetBlock->attribute( 'id' ), (string)$collection->attribute( 'collection_type' ) );
            $newCollection->setAttribute( 'offset_value', (int)$collection->attribute( 'offset_value' ) );
            $newCollection->setAttribute( 'limit_value', (int)$collection->attribute( 'limit_value' ) );
            $newCollection->setAttribute( 'status', (int)$targetBlock->attribute( 'status' ) );
            $newCollection->store();

            $query = expLayoutsCollectionQuery::fetchByCollection( (int)$collection->attribute( 'id' ) );
            if ( $query )
            {
                expLayoutsCollectionQuery::set( (int)$newCollection->attribute( 'id' ), (string)$query->attribute( 'query_type' ), (string)$query->attribute( 'parameters' ) );
            }

            $items = expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) );
            foreach ( $items as $item )
            {
                $newItem = expLayoutsCollectionItem::create(
                    (int)$newCollection->attribute( 'id' ),
                    (int)$item->attribute( 'value_id' ),
                    (string)$item->attribute( 'value_type' ),
                    (string)$item->attribute( 'item_type' )
                );
                $newItem->setAttribute( 'position', (int)$item->attribute( 'position' ) );
                $newItem->store();
            }
        }
    }
}
