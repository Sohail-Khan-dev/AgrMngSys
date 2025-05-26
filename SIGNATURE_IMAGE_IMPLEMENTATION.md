# Signature Image Implementation

## Overview
This implementation changes the signature handling from storing strings to storing and serving signature images.

## Changes Made

### 1. AgreementController.php
- **Added Storage facade import** for file handling
- **Modified createAgreement() method**:
  - Changed validation rule for `signature` from `nullable|string` to `nullable|image|mimes:jpeg,png,jpg,gif|max:2048`
  - Added image upload handling that stores files in `storage/app/public/signatures/`
  - Files are named with pattern: `signature_{user_id}_{agreement_id}_{timestamp}.{extension}`

- **Modified signAgreement() method**:
  - Added required validation for signature image: `required|image|mimes:jpeg,png,jpg,gif|max:2048`
  - Added logic to delete old signature files when updating
  - Stores new signature image with unique filename

- **Updated getAgreementUsers() method**:
  - Added `signature_url` field to response
  - Generates API URLs for signature images using `/api/signature/{filename}` endpoint

- **Added getSignatureImage() method**:
  - Serves signature images from storage
  - Returns proper MIME types
  - Handles file not found errors

### 2. routes/api.php
- **Added new route**: `GET /api/signature/{filename}` to serve signature images

### 3. Storage Structure
- Signature images are stored in `storage/app/public/signatures/`
- Files are accessible via the storage link at `public/storage/signatures/`

## API Changes

### createAgreement
**Before**: `signature` field accepted string values
**After**: `signature` field accepts image files (jpeg, png, jpg, gif, max 2MB)

### signAgreement  
**Before**: No signature parameter required
**After**: `signature` parameter required as image file (jpeg, png, jpg, gif, max 2MB)

### getAgreementUsers
**Before**: Returns signature as string value
**After**: Returns both `signature` (file path) and `signature_url` (API endpoint to access image)

### New Endpoint: getSignatureImage
**URL**: `GET /api/signature/{filename}`
**Purpose**: Serves signature images with proper MIME types
**Response**: Image file or 404 error if not found

## Usage Examples

### Creating Agreement with Signature
```javascript
const formData = new FormData();
formData.append('email', 'user@example.com');
formData.append('title', 'Agreement Title');
formData.append('slug', 'agreement-slug');
formData.append('agreement_file', 'Agreement content...');
formData.append('signature', signatureImageFile); // File object

fetch('/api/create_agreement', {
    method: 'POST',
    body: formData
});
```

### Signing Agreement
```javascript
const formData = new FormData();
formData.append('agreement_id', '123');
formData.append('email', 'user@example.com');
formData.append('signature', signatureImageFile); // File object

fetch('/api/signAgreement', {
    method: 'POST',
    body: formData
});
```

### Displaying Signature Images
```javascript
// Get agreement users
const response = await fetch('/api/getAgreementUsers', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ agreement_id: 123 })
});

const data = await response.json();
data.users.forEach(user => {
    if (user.signature_url) {
        // Display signature image
        const img = document.createElement('img');
        img.src = user.signature_url;
        document.body.appendChild(img);
    }
});
```

## File Management
- Old signature files are automatically deleted when users update their signatures
- Files are stored with unique names to prevent conflicts
- Maximum file size is 2MB
- Supported formats: JPEG, PNG, JPG, GIF

## Security Considerations
- File uploads are validated for type and size
- Files are stored outside the web root in Laravel's storage system
- Images are served through a controlled API endpoint
- File access is managed through Laravel's storage system

## Migration Notes
- Existing string signatures in the database will continue to work
- The system handles both old string signatures and new image paths
- No database migration is required as the signature field remains a string column
