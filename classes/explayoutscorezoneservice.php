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

    public function loadByLayoutAndIdentifier( $layoutId, $identifier, $status = null )
    {
        return expLayoutsZone::fetchByLayoutAndIdentifier( (int)$layoutId, trim( $identifier ), $status );
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

    /**
     * Point a zone at a zone of a shared layout, so it renders that zone's
     * blocks instead of its own.
     *
     * Refused unless the target layout is marked shared and actually owns a
     * zone of that name, and unless the zone belongs to a draft: linking is
     * an edit like any other and has to be publishable and discardable. A
     * layout may not link to itself.
     */
    public function link( $zoneId, $linkedLayoutId, $linkedZoneIdentifier )
    {
        $zone = $this->load( (int)$zoneId );
        if ( !$zone )
            return false;

        $linkedLayoutId = (int)$linkedLayoutId;
        $linkedZoneIdentifier = trim( (string)$linkedZoneIdentifier );

        if ( $linkedLayoutId <= 0 || $linkedZoneIdentifier === '' )
            return false;

        if ( $linkedLayoutId === (int)$zone->attribute( 'layout_id' ) )
            return false;

        $target = expLayoutsLayout::fetch( $linkedLayoutId );
        if ( !$target || !$target->isShared() )
            return false;

        if ( !expLayoutsZone::fetchByLayoutAndIdentifier( $linkedLayoutId, $linkedZoneIdentifier, null ) )
            return false;

        $zone->setAttribute( 'linked_layout_id', $linkedLayoutId );
        $zone->setAttribute( 'linked_zone_identifier', $linkedZoneIdentifier );
        $zone->store();
        return $zone;
    }

    public function unlink( $zoneId )
    {
        $zone = $this->load( (int)$zoneId );
        if ( !$zone )
            return false;

        $zone->setAttribute( 'linked_layout_id', null );
        $zone->setAttribute( 'linked_zone_identifier', null );
        $zone->store();
        return $zone;
    }
}
