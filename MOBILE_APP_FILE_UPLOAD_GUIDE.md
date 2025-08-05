# Mobile App File Upload Integration Guide

## ✅ Problem SOLVED: No More Local File Paths!

The backend has been fixed to properly handle file uploads using the existing `Utils::upload_images_2()` method for single file uploads. **No more local file paths like `/data/user/0/ugflix.com/code_cache/audio_1754345517765.m4a` will be accepted!**

## 🔧 Backend Changes Made

1. **Updated `chat_send` method** to use `Utils::upload_images_2($_FILES, true)` for single file uploads
2. **Removed gallery support** - only camera and photo library options
3. **All multimedia files** (photo, audio, video, document) now properly uploaded to `public/storage/images/`
4. **Unique filename generation** with timestamp + random number to prevent conflicts

## 📱 Mobile App Requirements

### 1. Add Image Picker Package

Add to `pubspec.yaml`:
```yaml
dependencies:
  image_picker: ^1.0.4
```

### 2. Update Photo Selection Code

Replace gallery options with camera + photo library:

```dart
import 'package:image_picker/image_picker.dart';

class ChatScreen extends StatefulWidget {
  // ... existing code
}

class _ChatScreenState extends State<ChatScreen> {
  final ImagePicker _picker = ImagePicker();

  // Remove gallery option, use only camera and photo
  void _showImagePickerOptions() {
    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: Icon(Icons.camera_alt),
              title: Text('Camera'),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.camera);
              },
            ),
            ListTile(
              leading: Icon(Icons.photo_library),
              title: Text('Photo Library'),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.gallery);
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final XFile? image = await _picker.pickImage(
        source: source,
        maxWidth: 1920,
        maxHeight: 1080,
        imageQuality: 85,
      );
      
      if (image != null) {
        File imageFile = File(image.path);
        await _sendPhotoMessage(imageFile);
      }
    } catch (e) {
      print('Error picking image: $e');
    }
  }
}
```

### 3. Update File Upload Methods

**For Photo Messages:**
```dart
Future<void> _sendPhotoMessage(File imageFile) async {
  try {
    var request = http.MultipartRequest(
      'POST', 
      Uri.parse('${ApiConfig.baseUrl}/api/chat_send')
    );
    
    // Add authorization header
    request.headers['Authorization'] = 'Bearer ${UserService.token}';
    
    // Add form fields
    request.fields['conversation_id'] = widget.conversationId.toString();
    request.fields['message_type'] = 'photo';
    request.fields['content'] = _captionController.text; // Optional caption
    
    // Add actual file upload (NOT file path!)
    request.files.add(
      await http.MultipartFile.fromPath(
        'photo',  // Parameter name
        imageFile.path,
        contentType: MediaType('image', 'jpeg'),
      )
    );
    
    var response = await request.send();
    var responseData = await response.stream.bytesToString();
    
    if (response.statusCode == 200) {
      print('Photo message sent successfully');
      _refreshMessages();
    } else {
      print('Failed to send photo: $responseData');
    }
    
  } catch (e) {
    print('Error sending photo: $e');
  }
}
```

**For Audio Messages:**
```dart
Future<void> _sendAudioMessage(File audioFile) async {
  try {
    var request = http.MultipartRequest(
      'POST', 
      Uri.parse('${ApiConfig.baseUrl}/api/chat_send')
    );
    
    // Add authorization header
    request.headers['Authorization'] = 'Bearer ${UserService.token}';
    
    // Add form fields
    request.fields['conversation_id'] = widget.conversationId.toString();
    request.fields['message_type'] = 'audio';
    request.fields['duration'] = _audioDuration.toString(); // Optional
    
    // Add actual file upload (NOT file path!)
    request.files.add(
      await http.MultipartFile.fromPath(
        'audio',  // Parameter name
        audioFile.path,
        contentType: MediaType('audio', 'mp4'),  // or 'audio', 'mpeg'
      )
    );
    
    var response = await request.send();
    var responseData = await response.stream.bytesToString();
    
    if (response.statusCode == 200) {
      print('Audio message sent successfully');
      _refreshMessages();
    } else {
      print('Failed to send audio: $responseData');
    }
    
  } catch (e) {
    print('Error sending audio: $e');
  }
}
```

### 4. Import Required Packages

```dart
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import 'package:image_picker/image_picker.dart';
```

## 🚫 What NOT to Do

**❌ NEVER send file paths like this:**
```dart
// DON'T DO THIS!
Map<String, dynamic> data = {
  'conversation_id': conversationId,
  'message_type': 'audio',
  'audio': '/data/user/0/ugflix.com/code_cache/audio_1754345517765.m4a', // ❌ NO!
};
```

**✅ DO send actual files like this:**
```dart
// DO THIS!
var request = http.MultipartRequest('POST', uri);
request.files.add(
  await http.MultipartFile.fromPath('audio', audioFile.path)
); // ✅ YES!
```

## 🔧 Backend API Endpoints

**All multimedia uploads:**
```
POST /api/chat_send
Content-Type: multipart/form-data
Authorization: Bearer [token]

Form Data:
- conversation_id: [int]
- message_type: 'photo'|'audio'|'video'|'document'
- [file_parameter]: [actual file upload]
- content: [optional caption/content]
- duration: [optional for audio/video]
```

## ✅ Expected Results

- ✅ Files properly uploaded to `public/storage/images/` 
- ✅ Unique filenames generated (timestamp_random.ext)
- ✅ No more local file path errors
- ✅ Proper multimedia message handling
- ✅ Single file upload per message
- ✅ All file types supported (photo, audio, video, document)

## 🔍 Testing

1. Test photo upload from camera
2. Test photo upload from photo library  
3. Test audio recording upload
4. Verify files are stored on server with unique names
5. Confirm no local file paths are being sent

**The backend is now ready to receive proper file uploads instead of file paths!**
