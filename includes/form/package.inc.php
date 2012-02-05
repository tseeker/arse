<?php

$package[ 'requires' ][] = 'box';

$package[ 'files' ][] = 'field';
$package[ 'files' ][] = 'form';
$package[ 'files' ][] = 'view';
$package[ 'files' ][] = 'ctrl';
$package[ 'files' ][] = 'modifiers';
$package[ 'files' ][] = 'validators';

$package[ 'views' ][] = 'form';
$package[ 'ctrls' ][] = 'form';

$package[ 'extras' ][] = 'Field';
$package[ 'extras' ][] = 'FieldModifier';
$package[ 'extras' ][] = 'FieldValidator';
$package[ 'extras' ][] = 'Form';
$package[ 'extras' ][] = 'FormAware';

$package[ 'extras' ][] = 'Modifier_TrimString';
$package[ 'extras' ][] = 'Validator_StringLength';
$package[ 'extras' ][] = 'Validator_InArray';
$package[ 'extras' ][] = 'Validator_IntValue';
$package[ 'extras' ][] = 'Validator_Email';
