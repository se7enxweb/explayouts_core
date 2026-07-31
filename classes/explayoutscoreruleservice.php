<?php

class expLayoutsCoreRuleService
{
    public function load( $ruleId )
    {
        return expLayoutsRule::fetch( (int)$ruleId );
    }

    public function listAll( $enabledOnly = false )
    {
        if ( $enabledOnly )
            return expLayoutsRule::fetchEnabled();

        return eZPersistentObject::fetchObjectList(
            expLayoutsRule::definition(),
            null, null,
            array( 'priority' => 'desc', 'id' => 'desc' ),
            null, true
        );
    }

    public function create( $layoutId, $priority = 0, $enabled = 1 )
    {
        $rule = expLayoutsRule::create( (int)$layoutId, (int)$priority );
        $rule->setAttribute( 'enabled', $enabled ? 1 : 0 );
        $rule->store();
        return $rule;
    }

    public function update( $ruleId, $attributes )
    {
        $rule = $this->load( (int)$ruleId );
        if ( !$rule )
            return false;

        foreach ( $attributes as $key => $value )
        {
            if ( in_array( $key, array( 'layout_id', 'priority', 'enabled' ) ) )
                $rule->setAttribute( $key, $value );
        }
        $rule->store();
        return $rule;
    }

    public function setTargets( $ruleId, $targets )
    {
        $rule = $this->load( (int)$ruleId );
        if ( !$rule )
            return false;

        foreach ( $rule->targets() as $target )
            $target->remove();

        foreach ( $targets as $target )
        {
            if ( !isset( $target['type'] ) || trim( $target['type'] ) === '' )
                continue;

            $newTarget = new expLayoutsRuleTarget( array(
                'rule_id' => (int)$rule->attribute( 'id' ),
                'target_type' => trim( $target['type'] ),
                'target_value' => isset( $target['value'] ) ? trim( $target['value'] ) : '',
            ) );
            $newTarget->store();
        }

        return $rule;
    }

    public function setConditions( $ruleId, $conditions )
    {
        $rule = $this->load( (int)$ruleId );
        if ( !$rule )
            return false;

        foreach ( $rule->conditions() as $condition )
            $condition->remove();

        foreach ( $conditions as $condition )
        {
            if ( !isset( $condition['type'] ) || trim( $condition['type'] ) === '' )
                continue;

            $newCondition = new expLayoutsRuleCondition( array(
                'rule_id' => (int)$rule->attribute( 'id' ),
                'condition_type' => trim( $condition['type'] ),
                'condition_value' => isset( $condition['value'] ) ? trim( $condition['value'] ) : '',
            ) );
            $newCondition->store();
        }

        return $rule;
    }

    public function copy( $ruleId )
    {
        $rule = $this->load( (int)$ruleId );
        if ( !$rule )
            return false;

        $copy = $this->create( (int)$rule->attribute( 'layout_id' ), (int)$rule->attribute( 'priority' ), (int)$rule->attribute( 'enabled' ) );

        foreach ( $rule->targets() as $target )
        {
            $newTarget = new expLayoutsRuleTarget( array(
                'rule_id' => (int)$copy->attribute( 'id' ),
                'target_type' => $target->attribute( 'target_type' ),
                'target_value' => $target->attribute( 'target_value' ),
            ) );
            $newTarget->store();
        }

        foreach ( $rule->conditions() as $condition )
        {
            $newCondition = new expLayoutsRuleCondition( array(
                'rule_id' => (int)$copy->attribute( 'id' ),
                'condition_type' => $condition->attribute( 'condition_type' ),
                'condition_value' => $condition->attribute( 'condition_value' ),
            ) );
            $newCondition->store();
        }

        return $copy;
    }

    public function delete( $ruleId )
    {
        $rule = $this->load( (int)$ruleId );
        if ( !$rule )
            return false;

        foreach ( $rule->targets() as $target )
            $target->remove();

        foreach ( $rule->conditions() as $condition )
            $condition->remove();

        $rule->remove();
        return true;
    }

    public function listByLayout( $layoutId )
    {
        return eZPersistentObject::fetchObjectList(
            expLayoutsRule::definition(),
            null,
            array( 'layout_id' => (int)$layoutId ),
            array( 'priority' => 'desc', 'id' => 'desc' ),
            null, true
        );
    }

    public function enable( $ruleId )
    {
        return $this->update( (int)$ruleId, array( 'enabled' => 1 ) );
    }

    public function disable( $ruleId )
    {
        return $this->update( (int)$ruleId, array( 'enabled' => 0 ) );
    }
}
