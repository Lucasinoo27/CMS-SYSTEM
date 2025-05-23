const fs = require('fs-extra');
const path = require('path');

async function copyTinyMCEAssets() {
  try {
    console.log('Copying TinyMCE assets to public directory...');
    
    // Source and destination paths
    const tinymceSrcDir = path.resolve(__dirname, 'node_modules', 'tinymce');
    const publicDir = path.resolve(__dirname, 'public');
    const publicTinyMCEDir = path.resolve(publicDir, 'tinymce');
    
    // Ensure public directory exists
    await fs.ensureDir(publicDir);
    
    // Remove existing tinymce directory if it exists
    if (await fs.pathExists(publicTinyMCEDir)) {
      await fs.remove(publicTinyMCEDir);
      console.log('Removed existing TinyMCE directory');
    }
    
    // Check if source directory exists
    if (await fs.pathExists(tinymceSrcDir)) {
      // Copy only the necessary files/folders from tinymce
      await fs.copy(
        path.join(tinymceSrcDir, 'icons'),
        path.join(publicTinyMCEDir, 'icons')
      );
      
      await fs.copy(
        path.join(tinymceSrcDir, 'models'),
        path.join(publicTinyMCEDir, 'models')
      );
      
      await fs.copy(
        path.join(tinymceSrcDir, 'plugins'),
        path.join(publicTinyMCEDir, 'plugins')
      );
      
      await fs.copy(
        path.join(tinymceSrcDir, 'skins'),
        path.join(publicTinyMCEDir, 'skins')
      );
      
      await fs.copy(
        path.join(tinymceSrcDir, 'themes'),
        path.join(publicTinyMCEDir, 'themes')
      );
      
      // Copy the tinymce.min.js file
      await fs.copy(
        path.join(tinymceSrcDir, 'tinymce.min.js'),
        path.join(publicTinyMCEDir, 'tinymce.min.js')
      );
      
      console.log('TinyMCE assets copied successfully!');
    } else {
      console.error('TinyMCE source directory not found:', tinymceSrcDir);
    }
  } catch (err) {
    console.error('Error copying TinyMCE assets:', err);
  }
}

copyTinyMCEAssets(); 