<?php

class expLayoutsCoreBlockService
{
    public function load( $blockId )
    {
        return expLayoutsBlock::fetch( (int)$blockId );
    }

    public function loadByZone( $zoneId, $status = null )
    {
        return expLayoutsBlock::fetchByZone( (int)$zoneId, $status );
    }

    public function create( $zoneId, $layoutId, $definitionIdentifier, $name = '', $viewType = 'default' )
    {
        $block = expLayoutsBlock::create( (int)$zoneId, (int)$layoutId, trim( $definitionIdentifier ), trim( $name ) );
        $block->setAttribute( 'view_type', trim( $viewType ) );
        $block->store();
        return $block;
    }

    public function update( $blockId, $attributes )
    {
        $block = $this->load( (int)$blockId );
        if ( !$block )
            return false;

        foreach ( $attributes as $key => $value )
        {
            if ( in_array( $key, array( 'name', 'view_type', 'zone_id', 'position' ) ) )
                $block->setAttribute( $key, $value );
        }
        $block->store();
        return $block;
    }

    public function move( $blockId, $targetZoneId )
    {
        $block = $this->load( (int)$blockId );
        if ( !$block )
            return false;

        $block->setAttribute( 'zone_id', (int)$targetZoneId );
        $block->store();
        return $block;
    }

    public function delete( $blockId )
    {
        $block = $this->load( (int)$blockId );
        if ( !$block )
            return false;

        $params = expLayoutsBlockParameter::fetchByBlock( (int)$block->attribute( 'id' ) );
        foreach ( $params as $param )
            $param->remove();

        $collection = expLayoutsCollection::fetchByBlock( (int)$block->attribute( 'id' ) );
        if ( $collection )
        {
            $items = expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) );
            foreach ( $items as $item )
                $item->remove();
            $collection->remove();
        }

        $block->remove();
        return true;
    }

    public function setParameters( $blockId, $parameters )
    {
        $block = $this->load( (int)$blockId );
        if ( !$block )
            return false;

        foreach ( $parameters as $name => $value )
            expLayoutsBlockParameter::set( (int)$block->attribute( 'id' ), trim( $name ), $value );

        return expLayoutsBlockParameter::fetchByBlock( (int)$block->attribute( 'id' ) );
    }

    public function setCollection( $blockId, $type = 'manual' )
    {
        $existing = expLayoutsCollection::fetchByBlock( (int)$blockId );
        if ( $existing )
            return $existing;

        $collection = expLayoutsCollection::create( (int)$blockId, trim( $type ) );
        $collection->store();
        return $collection;
    }
}
