<?php

namespace Drupal\image\Tests;

use Drupal\system\Tests\Image\ToolkitTestBase;

/**
 * Tests that the image effects pass parameters to the toolkit correctly.
 *
 * @group image
 */
class ImageEffectsTest extends ToolkitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image', 'image_test', 'image_module_test');

  /**
   * The image effect manager.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $manager;

  protected function setUp() {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.image.effect');
  }

  /**
   * Test the image_resize_effect() function.
   */
  function testResizeEffect() {
    $this->assertImageEffect('image_resize', array(
      'width' => 1,
      'height' => 2,
    ));
    $this->assertToolkitOperationsCalled(array('resize'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['resize'][0][0], 1, 'Width was passed correctly');
    $this->assertEqual($calls['resize'][0][1], 2, 'Height was passed correctly');
  }

  /**
   * Test the image_scale_effect() function.
   */
  function testScaleEffect() {
    // @todo: need to test upscaling.
    $this->assertImageEffect('image_scale', array(
      'width' => 10,
      'height' => 10,
    ));
    $this->assertToolkitOperationsCalled(array('scale'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['scale'][0][0], 10, 'Width was passed correctly');
    $this->assertEqual($calls['scale'][0][1], 10, 'Height was based off aspect ratio and passed correctly');
  }

  /**
   * Test the image_crop_effect() function.
   */
  function testCropEffect() {
    // @todo should test the keyword offsets.
    $this->assertImageEffect('image_crop', array(
      'anchor' => 'top-1',
      'width' => 3,
      'height' => 4,
    ));
    $this->assertToolkitOperationsCalled(array('crop'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['crop'][0][0], 0, 'X was passed correctly');
    $this->assertEqual($calls['crop'][0][1], 1, 'Y was passed correctly');
    $this->assertEqual($calls['crop'][0][2], 3, 'Width was passed correctly');
    $this->assertEqual($calls['crop'][0][3], 4, 'Height was passed correctly');
  }

  /**
   * Tests the ConvertImageEffect plugin.
   */
  function testConvertEffect() {
    // Test jpeg.
    $this->assertImageEffect('image_convert', array(
      'extension' => 'jpeg',
    ));
    $this->assertToolkitOperationsCalled(array('convert'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['convert'][0][0], 'jpeg', 'Extension was passed correctly');
  }

  /**
   * Test the image_scale_and_crop_effect() function.
   */
  function testScaleAndCropEffect() {
    $this->assertImageEffect('image_scale_and_crop', array(
      'width' => 5,
      'height' => 10,
    ));
    $this->assertToolkitOperationsCalled(array('scale_and_crop'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['scale_and_crop'][0][0], 5, 'Width was computed and passed correctly');
    $this->assertEqual($calls['scale_and_crop'][0][1], 10, 'Height was computed and passed correctly');
  }

  /**
   * Test the image_desaturate_effect() function.
   */
  function testDesaturateEffect() {
    $this->assertImageEffect('image_desaturate', array());
    $this->assertToolkitOperationsCalled(array('desaturate'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual(count($calls['desaturate'][0]), 0, 'No parameters were passed.');
  }

  /**
   * Test the image_rotate_effect() function.
   */
  function testRotateEffect() {
    // @todo: need to test with 'random' => TRUE
    $this->assertImageEffect('image_rotate', array(
      'degrees' => 90,
      'bgcolor' => '#fff',
    ));
    $this->assertToolkitOperationsCalled(array('rotate'));

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    $this->assertEqual($calls['rotate'][0][0], 90, 'Degrees were passed correctly');
    $this->assertEqual($calls['rotate'][0][1], '#fff', 'Background color was passed correctly');
  }

  /**
   * Test image effect caching.
   */
  function testImageEffectsCaching() {
    $image_effect_definitions_called = &drupal_static('image_module_test_image_effect_info_alter');

    // First call should grab a fresh copy of the data.
    $manager = $this->container->get('plugin.manager.image.effect');
    $effects = $manager->getDefinitions();
    $this->assertTrue($image_effect_definitions_called === 1, 'image_effect_definitions() generated data.');

    // Second call should come from cache.
    drupal_static_reset('image_module_test_image_effect_info_alter');
    $cached_effects = $manager->getDefinitions();
    $this->assertTrue($image_effect_definitions_called === 0, 'image_effect_definitions() returned data from cache.');

    $this->assertTrue($effects == $cached_effects, 'Cached effects are the same as generated effects.');
  }

  /**
   * Asserts the effect processing of an image effect plugin.
   *
   * @param string $effect_name
   *   The name of the image effect to test.
   * @param array $data
   *   The data to pass to the image effect.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertImageEffect($effect_name, array $data) {
    $effect = $this->manager->createInstance($effect_name, array('data' => $data));
    return $this->assertTrue($effect->applyEffect($this->image), 'Function returned the expected value.');
  }

}
