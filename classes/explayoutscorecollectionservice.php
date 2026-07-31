<?php

class expLayoutsCoreCollectionService
{
    public function load( $collectionId )
    {
        return expLayoutsCollection::fetch( (int)$collectionId );
    }

    public function loadByBlock( $blockId )
    {
        return expLayoutsCollection::fetchByBlock( (int)$blockId );
    }

    public function create( $blockId, $type = 'manual' )
    {
        $collection = expLayoutsCollection::create( (int)$blockId, trim( $type ) );
        $collection->store();
        return $collection;
    }

    public function update( $collectionId, $attributes )
    {
        $collection = $this->load( (int)$collectionId );
        if ( !$collection )
            return false;

        foreach ( $attributes as $key => $value )
        {
            if ( in_array( $key, array( 'collection_type', 'offset_value', 'limit_value' ) ) )
                $collection->setAttribute( $key, $value );
        }
        $collection->store();
        return $collection;
    }

    public function delete( $collectionId )
    {
        $collection = $this->load( (int)$collectionId );
        if ( !$collection )
            return false;

        $items = expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) );
        foreach ( $items as $item )
            $item->remove();

        $collection->remove();
        return true;
    }

    public function addItem( $collectionId, $nodeId )
    {
        $item = expLayoutsCollectionItem::create( (int)$collectionId, (int)$nodeId );
        $item->store();
        return $item;
    }

    public function removeItem( $itemId )
    {
        $item = expLayoutsCollectionItem::fetch( (int)$itemId );
        if ( !$item )
            return false;

        $item->remove();
        return true;
    }

    public function loadItems( $collectionId )
    {
        return expLayoutsCollectionItem::fetchByCollection( (int)$collectionId );
    }

    public function updateItem( $itemId, $attributes )
    {
        $item = expLayoutsCollectionItem::fetch( (int)$itemId );
        if ( !$item )
            return false;

        foreach ( $attributes as $key => $value )
        {
            if ( in_array( $key, array( 'position', 'value_type', 'value_id', 'item_type' ) ) )
                $item->setAttribute( $key, $value );
        }
        $item->store();
        return $item;
    }

    public function setItems( $collectionId, $items )
    {
        $collection = $this->load( (int)$collectionId );
        if ( !$collection )
            return false;

        $existing = expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) );
        foreach ( $existing as $item )
            $item->remove();

        foreach ( $items as $itemData )
        {
            $valueId = isset( $itemData['value_id'] ) ? (int)$itemData['value_id'] : 0;
            $valueType = isset( $itemData['value_type'] ) ? $itemData['value_type'] : 'ez_content';
            $itemType = isset( $itemData['item_type'] ) ? $itemData['item_type'] : 'manual';
            $position = isset( $itemData['position'] ) ? (int)$itemData['position'] : 0;
            if ( $valueId > 0 )
            {
                $item = expLayoutsCollectionItem::create( (int)$collection->attribute( 'id' ), $valueId, $valueType, $itemType );
                $item->setAttribute( 'position', $position );
                $item->store();
            }
        }

        return expLayoutsCollectionItem::fetchByCollection( (int)$collection->attribute( 'id' ) );
    }
}
