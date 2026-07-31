<?php

class expLayoutsCoreLayoutService
{
    public function load( $id, $status = null )
    {
        return expLayoutsLayout::fetch( (int)$id );
    }

    public function loadDraft( $id )
    {
        return $this->load( (int)$id );
    }

    public function loadPublished( $id )
    {
        return $this->load( (int)$id );
    }

    public function listAll( $status = null )
    {
        return expLayoutsLayout::fetchList( $status );
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
        $layout = $this->loadDraft( (int)$id );
        if ( !$layout )
            return false;

        $layout->publish();
        return $this->loadPublished( (int)$id );
    }

    public function copy( $id )
    {
        $source = $this->load( (int)$id );
        if ( !$source )
            return false;

        $newIdentifier = trim( $source->attribute( 'identifier' ) ) . '_' . time();
        $copy = $this->create(
            $newIdentifier,
            $source->attribute( 'name' ) . ' (copy)',
            $source->attribute( 'layout_type' )
        );

        $zones = expLayoutsZone::fetchByLayout( (int)$source->attribute( 'id' ), (int)$source->attribute( 'status' ) );
        foreach ( $zones as $zone )
        {
            $newZone = expLayoutsZone::create( (int)$copy->attribute( 'id' ), $zone->attribute( 'identifier' ), (int)$copy->attribute( 'status' ) );
            $newZone->setAttribute( 'position', $zone->attribute( 'position' ) );
            $newZone->store();

            $blocks = expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), (int)$source->attribute( 'status' ) );
            foreach ( $blocks as $block )
            {
                $newBlock = expLayoutsBlock::create(
                    (int)$newZone->attribute( 'id' ),
                    (int)$copy->attribute( 'id' ),
                    $block->attribute( 'definition_identifier' ),
                    $block->attribute( 'name' )
                );
                $newBlock->setAttribute( 'view_type', $block->attribute( 'view_type' ) );
                $newBlock->setAttribute( 'position', $block->attribute( 'position' ) );
                $newBlock->store();

                $params = expLayoutsBlockParameter::fetchByBlock( (int)$block->attribute( 'id' ) );
                foreach ( $params as $param )
                {
                    expLayoutsBlockParameter::set( (int)$newBlock->attribute( 'id' ), $param->attribute( 'name' ), $param->attribute( 'value' ) );
                }
            }
        }

        return $copy;
    }

    public function delete( $id )
    {
        $layout = $this->load( (int)$id );
        if ( !$layout )
            return false;

        $zones = expLayoutsZone::fetchByLayout( (int)$layout->attribute( 'id' ), (int)$layout->attribute( 'status' ) );
        foreach ( $zones as $zone )
        {
            $blocks = expLayoutsBlock::fetchByZone( (int)$zone->attribute( 'id' ), (int)$layout->attribute( 'status' ) );
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
        }

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
}
