<?php

class expLayoutsCoreZoneService
{
    public function load( $zoneId )
    {
        return expLayoutsZone::fetch( (int)$zoneId );
    }

    public function loadByLayout( $layoutId, $status = null )
    {
        return expLayoutsZone::fetchByLayout( (int)$layoutId, $status );
    }

    public function create( $layoutId, $identifier, $status = 1 )
    {
        $zone = expLayoutsZone::create( (int)$layoutId, trim( $identifier ), (int)$status );
        $zone->store();
        return $zone;
    }

    public function update( $zoneId, $attributes )
    {
        $zone = $this->load( (int)$zoneId );
        if ( !$zone )
            return false;

        foreach ( $attributes as $key => $value )
        {
            if ( in_array( $key, array( 'identifier', 'position' ) ) )
                $zone->setAttribute( $key, $value );
        }
        $zone->store();
        return $zone;
    }

    public function delete( $zoneId )
    {
        $zone = $this->load( (int)$zoneId );
        if ( !$zone )
            return false;

        $blocks = expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), (int)$zone->attribute( 'status' ) );
        foreach ( $blocks as $block )
        {
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
        }

        $zone->remove();
        return true;
    }

    public function countBlocks( $zoneId )
    {
        return count( expLayoutsBlock::fetchByZone( (int)$zoneId ) );
    }

    public function setLinkedLayout( $zoneId, $linkedLayoutId )
    {
        $zone = $this->load( (int)$zoneId );
        if ( !$zone )
            return false;

        $zone->setAttribute( 'linked_layout_id', (int)$linkedLayoutId );
        $zone->store();
        return $zone;
    }
}
