<?php

it('loads translations files correctly', function () {
    expect(trans('shipping::shippo.services.usps_first'))->toEqual('First Class');
});
