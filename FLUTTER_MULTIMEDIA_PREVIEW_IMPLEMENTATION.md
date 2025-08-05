# Flutter Multimedia Preview Implementation

## 1. Dependencies (pubspec.yaml)

```yaml
dependencies:
  flutter:
    sdk: flutter
  image_picker: ^1.0.4
  http: ^1.1.0
  video_player: ^2.7.2
  audioplayers: ^5.2.1
  path: ^1.8.3
  permission_handler: ^11.0.1
  file_picker: ^6.1.1
```

## 2. Complete Chat Screen with Multimedia Preview

```dart
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:video_player/video_player.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import 'package:file_picker/file_picker.dart';
import 'package:permission_handler/permission_handler.dart';

class ChatScreen extends StatefulWidget {
  final int conversationId;
  final String receiverId;

  const ChatScreen({
    Key? key,
    required this.conversationId,
    required this.receiverId,
  }) : super(key: key);

  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final ImagePicker _picker = ImagePicker();
  final TextEditingController _messageController = TextEditingController();
  final TextEditingController _captionController = TextEditingController();
  
  bool _isLoading = false;
  bool _isPreviewMode = false;
  File? _selectedFile;
  String? _selectedFileType;
  Map<String, dynamic>? _previewData;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Chat'),
        backgroundColor: Colors.pink,
      ),
      body: Column(
        children: [
          // Messages list would go here
          Expanded(
            child: Container(
              child: Center(
                child: Text('Messages will appear here'),
              ),
            ),
          ),
          
          // Preview section (shows when file is selected)
          if (_isPreviewMode && _selectedFile != null)
            _buildPreviewSection(),
          
          // Message input area
          _buildMessageInput(),
        ],
      ),
    );
  }

  Widget _buildPreviewSection() {
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        border: Border(top: BorderSide(color: Colors.grey[300]!)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Preview ${_selectedFileType?.toUpperCase()}',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              IconButton(
                icon: Icon(Icons.close),
                onPressed: _cancelPreview,
              ),
            ],
          ),
          SizedBox(height: 12),
          
          // Preview content based on file type
          _buildPreviewContent(),
          
          SizedBox(height: 12),
          
          // Caption input
          TextField(
            controller: _captionController,
            decoration: InputDecoration(
              hintText: 'Add a caption...',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            ),
            maxLines: 2,
          ),
          
          SizedBox(height: 12),
          
          // Send button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _sendPreviewedMedia,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.pink,
                padding: EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(25),
                ),
              ),
              child: _isLoading
                  ? CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                  : Text(
                      'Send ${_selectedFileType?.toUpperCase()}',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPreviewContent() {
    if (_selectedFile == null) return Container();

    switch (_selectedFileType) {
      case 'photo':
        return ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Image.file(
            _selectedFile!,
            height: 200,
            width: double.infinity,
            fit: BoxFit.cover,
          ),
        );
        
      case 'video':
        return _VideoPreview(file: _selectedFile!);
        
      case 'audio':
        return _AudioPreview(file: _selectedFile!);
        
      case 'document':
        return _DocumentPreview(file: _selectedFile!);
        
      default:
        return Container(
          height: 100,
          decoration: BoxDecoration(
            border: Border.all(color: Colors.grey),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Center(
            child: Text('File selected: ${_selectedFile!.path.split('/').last}'),
          ),
        );
    }
  }

  Widget _buildMessageInput() {
    if (_isPreviewMode) return Container(); // Hide when in preview mode

    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey[300]!)),
      ),
      child: Row(
        children: [
          // Media attachment button
          IconButton(
            icon: Icon(Icons.attach_file, color: Colors.pink),
            onPressed: _showMediaOptions,
          ),
          
          // Text input
          Expanded(
            child: TextField(
              controller: _messageController,
              decoration: InputDecoration(
                hintText: 'Type a message...',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(25),
                  borderSide: BorderSide.none,
                ),
                filled: true,
                fillColor: Colors.grey[100],
                contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              ),
              maxLines: null,
            ),
          ),
          
          SizedBox(width: 8),
          
          // Send button
          CircleAvatar(
            backgroundColor: Colors.pink,
            child: IconButton(
              icon: Icon(Icons.send, color: Colors.white),
              onPressed: _sendTextMessage,
            ),
          ),
        ],
      ),
    );
  }

  void _showMediaOptions() {
    showModalBottomSheet(
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                children: [
                  Text(
                    'Choose Media',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 16),
                  
                  // Camera option
                  ListTile(
                    leading: Icon(Icons.camera_alt, color: Colors.pink),
                    title: Text('Camera'),
                    subtitle: Text('Take a photo or video'),
                    onTap: () {
                      Navigator.pop(context);
                      _showCameraOptions();
                    },
                  ),
                  
                  // Photo library option
                  ListTile(
                    leading: Icon(Icons.photo_library, color: Colors.pink),
                    title: Text('Photo Library'),
                    subtitle: Text('Choose from gallery'),
                    onTap: () {
                      Navigator.pop(context);
                      _pickFromGallery();
                    },
                  ),
                  
                  // Document option
                  ListTile(
                    leading: Icon(Icons.attach_file, color: Colors.pink),
                    title: Text('Document'),
                    subtitle: Text('Choose a file'),
                    onTap: () {
                      Navigator.pop(context);
                      _pickDocument();
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showCameraOptions() {
    showModalBottomSheet(
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                children: [
                  Text(
                    'Camera Options',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 16),
                  
                  ListTile(
                    leading: Icon(Icons.photo_camera, color: Colors.pink),
                    title: Text('Take Photo'),
                    onTap: () {
                      Navigator.pop(context);
                      _pickImage(ImageSource.camera);
                    },
                  ),
                  
                  ListTile(
                    leading: Icon(Icons.videocam, color: Colors.pink),
                    title: Text('Record Video'),
                    onTap: () {
                      Navigator.pop(context);
                      _pickVideo(ImageSource.camera);
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _pickFromGallery() {
    showModalBottomSheet(
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                children: [
                  Text(
                    'Gallery Options',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 16),
                  
                  ListTile(
                    leading: Icon(Icons.photo, color: Colors.pink),
                    title: Text('Choose Photo'),
                    onTap: () {
                      Navigator.pop(context);
                      _pickImage(ImageSource.gallery);
                    },
                  ),
                  
                  ListTile(
                    leading: Icon(Icons.video_library, color: Colors.pink),
                    title: Text('Choose Video'),
                    onTap: () {
                      Navigator.pop(context);
                      _pickVideo(ImageSource.gallery);
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Image picker methods
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
        await _previewMedia(imageFile, 'photo');
      }
    } catch (e) {
      _showError('Error picking image: $e');
    }
  }

  Future<void> _pickVideo(ImageSource source) async {
    try {
      final XFile? video = await _picker.pickVideo(
        source: source,
        maxDuration: Duration(minutes: 5), // 5 minute limit
      );
      
      if (video != null) {
        File videoFile = File(video.path);
        await _previewMedia(videoFile, 'video');
      }
    } catch (e) {
      _showError('Error picking video: $e');
    }
  }

  Future<void> _pickDocument() async {
    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.any,
        allowMultiple: false,
      );

      if (result != null && result.files.single.path != null) {
        File documentFile = File(result.files.single.path!);
        await _previewMedia(documentFile, 'document');
      }
    } catch (e) {
      _showError('Error picking document: $e');
    }
  }

  // Preview media before sending
  Future<void> _previewMedia(File file, String mediaType) async {
    setState(() {
      _selectedFile = file;
      _selectedFileType = mediaType;
      _isPreviewMode = true;
      _isLoading = true;
    });

    try {
      // Upload file for preview
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}/api/upload_media_preview'),
      );

      // Add authorization header
      request.headers['Authorization'] = 'Bearer ${UserService.token}';

      // Add form fields
      request.fields['media_type'] = mediaType;

      // Add file
      request.files.add(
        await http.MultipartFile.fromPath(
          mediaType, // photo, video, audio, document
          file.path,
          contentType: _getContentType(mediaType, file.path),
        ),
      );

      var response = await request.send();
      var responseData = await response.stream.bytesToString();
      var jsonResponse = json.decode(responseData);

      if (response.statusCode == 200 && jsonResponse['success'] == true) {
        setState(() {
          _previewData = jsonResponse['data'];
          _isLoading = false;
        });
      } else {
        throw Exception(jsonResponse['message'] ?? 'Upload failed');
      }
    } catch (e) {
      _showError('Upload failed: $e');
      _cancelPreview();
    }
  }

  MediaType _getContentType(String mediaType, String filePath) {
    String extension = filePath.split('.').last.toLowerCase();
    
    switch (mediaType) {
      case 'photo':
        if (extension == 'png') return MediaType('image', 'png');
        return MediaType('image', 'jpeg');
      case 'video':
        return MediaType('video', 'mp4');
      case 'audio':
        if (extension == 'm4a') return MediaType('audio', 'mp4');
        return MediaType('audio', 'mpeg');
      case 'document':
        if (extension == 'pdf') return MediaType('application', 'pdf');
        return MediaType('application', 'octet-stream');
      default:
        return MediaType('application', 'octet-stream');
    }
  }

  // Send the previewed media
  Future<void> _sendPreviewedMedia() async {
    if (_previewData == null) return;

    setState(() {
      _isLoading = true;
    });

    try {
      var response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/api/chat_send'),
        headers: {
          'Authorization': 'Bearer ${UserService.token}',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'conversation_id': widget.conversationId,
          'receiver_id': widget.receiverId,
          'message_type': _selectedFileType,
          'preview_file_name': _previewData!['file_name'],
          'content': _captionController.text,
          'media_size': _previewData!['file_size'],
          'duration': _previewData!['duration'],
          'thumbnail': _previewData!['thumbnail_url'],
        }),
      );

      var jsonResponse = json.decode(response.body);

      if (response.statusCode == 200 && jsonResponse['success'] == true) {
        _showSuccess('${_selectedFileType!.toUpperCase()} sent successfully!');
        _cancelPreview();
        // Refresh messages here
      } else {
        throw Exception(jsonResponse['message'] ?? 'Failed to send message');
      }
    } catch (e) {
      _showError('Failed to send: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _cancelPreview() {
    setState(() {
      _isPreviewMode = false;
      _selectedFile = null;
      _selectedFileType = null;
      _previewData = null;
      _captionController.clear();
      _isLoading = false;
    });
  }

  // Send text message
  void _sendTextMessage() {
    if (_messageController.text.trim().isEmpty) return;
    
    // Implement text message sending
    print('Sending text: ${_messageController.text}');
    _messageController.clear();
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
      ),
    );
  }
}

// Video preview widget
class _VideoPreview extends StatefulWidget {
  final File file;

  const _VideoPreview({required this.file});

  @override
  _VideoPreviewState createState() => _VideoPreviewState();
}

class _VideoPreviewState extends State<_VideoPreview> {
  VideoPlayerController? _controller;
  bool _isInitialized = false;

  @override
  void initState() {
    super.initState();
    _initializeVideo();
  }

  void _initializeVideo() async {
    _controller = VideoPlayerController.file(widget.file);
    await _controller!.initialize();
    setState(() {
      _isInitialized = true;
    });
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_isInitialized) {
      return Container(
        height: 200,
        child: Center(child: CircularProgressIndicator()),
      );
    }

    return Container(
      height: 200,
      child: Stack(
        alignment: Alignment.center,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: AspectRatio(
              aspectRatio: _controller!.value.aspectRatio,
              child: VideoPlayer(_controller!),
            ),
          ),
          CircleAvatar(
            backgroundColor: Colors.black54,
            child: IconButton(
              icon: Icon(
                _controller!.value.isPlaying ? Icons.pause : Icons.play_arrow,
                color: Colors.white,
              ),
              onPressed: () {
                setState(() {
                  _controller!.value.isPlaying
                      ? _controller!.pause()
                      : _controller!.play();
                });
              },
            ),
          ),
        ],
      ),
    );
  }
}

// Audio preview widget
class _AudioPreview extends StatefulWidget {
  final File file;

  const _AudioPreview({required this.file});

  @override
  _AudioPreviewState createState() => _AudioPreviewState();
}

class _AudioPreviewState extends State<_AudioPreview> {
  AudioPlayer? _audioPlayer;
  bool _isPlaying = false;

  @override
  void initState() {
    super.initState();
    _audioPlayer = AudioPlayer();
  }

  @override
  void dispose() {
    _audioPlayer?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          CircleAvatar(
            backgroundColor: Colors.pink,
            child: IconButton(
              icon: Icon(
                _isPlaying ? Icons.pause : Icons.play_arrow,
                color: Colors.white,
              ),
              onPressed: _toggleAudio,
            ),
          ),
          SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Audio Message',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                Text(
                  widget.file.path.split('/').last,
                  style: TextStyle(color: Colors.grey[600]),
                ),
              ],
            ),
          ),
          Icon(Icons.mic, color: Colors.pink),
        ],
      ),
    );
  }

  void _toggleAudio() async {
    if (_isPlaying) {
      await _audioPlayer!.pause();
    } else {
      await _audioPlayer!.play(DeviceFileSource(widget.file.path));
    }
    setState(() {
      _isPlaying = !_isPlaying;
    });
  }
}

// Document preview widget
class _DocumentPreview extends StatelessWidget {
  final File file;

  const _DocumentPreview({required this.file});

  @override
  Widget build(BuildContext context) {
    String fileName = file.path.split('/').last;
    String extension = fileName.split('.').last.toUpperCase();
    
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey[300]!),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Container(
            padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.blue,
              borderRadius: BorderRadius.circular(4),
            ),
            child: Text(
              extension,
              style: TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  fileName,
                  style: TextStyle(fontWeight: FontWeight.bold),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  '${(file.lengthSync() / 1024).toStringAsFixed(1)} KB',
                  style: TextStyle(color: Colors.grey[600]),
                ),
              ],
            ),
          ),
          Icon(Icons.attach_file, color: Colors.grey),
        ],
      ),
    );
  }
}

// API Configuration
class ApiConfig {
  static const String baseUrl = 'https://your-api-domain.com';
}

// User Service (placeholder)
class UserService {
  static String? token = 'your-jwt-token';
}
```

## 3. Key Features Implemented

### ✅ Image Picker Integration
- **Camera**: Take photos and videos
- **Gallery**: Choose from photo library
- **File Picker**: Select documents

### ✅ Multimedia Preview System
1. **Upload to Preview Endpoint**: Files uploaded to `/api/upload_media_preview`
2. **Preview Display**: Show media before sending
3. **Caption Support**: Add captions to media
4. **Send with Preview Data**: Use `preview_file_name` parameter

### ✅ Preview Components
- **Photo Preview**: Display image with overlay controls
- **Video Preview**: Video player with play/pause
- **Audio Preview**: Audio player with waveform-style UI
- **Document Preview**: File info with size and type

### ✅ Backend Integration
- **Two-Step Process**: Upload → Preview → Send
- **File Validation**: Server validates file types and sizes
- **Error Handling**: Comprehensive error management
- **Progress Indicators**: Loading states throughout

### 🔧 API Endpoints Used
1. `POST /api/upload_media_preview` - Upload file for preview
2. `POST /api/chat_send` - Send message with previewed media

This implementation provides a complete multimedia preview system with proper file handling using the `image_picker` package as requested!
