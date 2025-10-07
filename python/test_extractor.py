#!/usr/bin/env python3
"""
Test script for YouTube caption extractor
"""

import json
import sys
import os

# Add current directory to path
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from youtube_caption_extractor import get_video_transcript, extract_video_id

def test_video(video_url):
    """Test caption extraction for a video"""
    print(f"Testing YouTube caption extraction for: {video_url}")
    print("=" * 60)
    
    # Extract video ID
    video_id = extract_video_id(video_url)
    if not video_id:
        print("❌ Failed to extract video ID")
        return False
    
    print(f"✅ Video ID extracted: {video_id}")
    
    # Get transcript
    result = get_video_transcript(video_id)
    
    if result['success']:
        print(f"✅ Transcript extracted successfully!")
        print(f"   Language: {result['language']}")
        print(f"   Word count: {result['word_count']}")
        print(f"   Character count: {result['character_count']}")
        print(f"   Segment count: {result['segment_count']}")
        if result.get('fallback'):
            print(f"   ⚠️  Used fallback language")
        
        print(f"\n📝 First 500 characters of transcript:")
        print("-" * 40)
        print(result['transcript'][:500] + "...")
        print("-" * 40)
        
        return True
    else:
        print(f"❌ Failed to extract transcript: {result['error']}")
        if 'fallback_error' in result:
            print(f"   Fallback also failed: {result['fallback_error']}")
        return False

if __name__ == '__main__':
    # Test with the provided video
    test_url = "https://www.youtube.com/watch?v=i1ucuvfyw0o"
    success = test_video(test_url)
    
    if success:
        print("\n🎉 Test completed successfully!")
    else:
        print("\n💥 Test failed!")
        sys.exit(1)
