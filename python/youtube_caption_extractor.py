#!/usr/bin/env python3
"""
YouTube Caption Extractor
Extracts captions/transcripts from YouTube videos using youtube-transcript-api
"""

import sys
import json
import argparse
from youtube_transcript_api import YouTubeTranscriptApi
from youtube_transcript_api.formatters import TextFormatter

def extract_video_id(url):
    """Extract video ID from YouTube URL"""
    import re
    
    patterns = [
        r'(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})',
        r'youtube\.com\/watch\?v=([^&\n?#]+)',
        r'youtube\.com\/embed\/([^&\n?#]+)',
        r'youtube\.com\/v\/([^&\n?#]+)',
        r'youtu\.be\/([^&\n?#]+)'
    ]
    
    for pattern in patterns:
        match = re.search(pattern, url)
        if match:
            return match.group(1)
    return None

def get_video_transcript(video_id, language='en'):
    """Get video transcript using youtube-transcript-api"""
    try:
        # Get transcript list
        transcript_list = YouTubeTranscriptApi().list(video_id)
        
        # Try to find transcript in specified language
        try:
            transcript = transcript_list.find_transcript([language])
            transcript_data = transcript.fetch()
            
            # Format as plain text
            formatter = TextFormatter()
            text = formatter.format_transcript(transcript_data)
            
            return {
                'success': True,
                'transcript': text,
                'language': language,
                'word_count': len(text.split()),
                'character_count': len(text),
                'segment_count': len(transcript_data)
            }
            
        except Exception:
            # Fallback to any available transcript
            transcript = transcript_list.find_generated_transcript(['en'])
            transcript_data = transcript.fetch()
            
            # Format as plain text
            formatter = TextFormatter()
            text = formatter.format_transcript(transcript_data)
            
            return {
                'success': True,
                'transcript': text,
                'language': transcript.language_code,
                'word_count': len(text.split()),
                'character_count': len(text),
                'segment_count': len(transcript_data),
                'fallback': True
            }
        
    except Exception as e:
        return {
            'success': False,
            'error': str(e)
        }

def main():
    """Main function for command line usage"""
    parser = argparse.ArgumentParser(description='Extract YouTube video captions')
    parser.add_argument('url', help='YouTube video URL')
    parser.add_argument('--language', '-l', default='en', help='Preferred language (default: en)')
    parser.add_argument('--output', '-o', help='Output file (optional)')
    
    args = parser.parse_args()
    
    # Extract video ID
    video_id = extract_video_id(args.url)
    if not video_id:
        print(json.dumps({
            'success': False,
            'error': 'Invalid YouTube URL'
        }))
        sys.exit(1)
    
    # Get transcript
    result = get_video_transcript(video_id, args.language)
    result['video_id'] = video_id
    result['url'] = args.url
    
    # Output result
    if args.output:
        with open(args.output, 'w', encoding='utf-8') as f:
            json.dump(result, f, indent=2, ensure_ascii=False)
        print(f"Result saved to {args.output}")
    else:
        print(json.dumps(result, indent=2, ensure_ascii=False))

if __name__ == '__main__':
    main()
