namespace Moodify.Models;

public class HomeViewModel
{
    public Dictionary<string, bool> DayTimesDictionary { get; set; } = new();
    public Dictionary<string, bool> MoodsDictionary { get; set; } = new();
    public Dictionary<string, bool> RelationshipsDictionary { get; set; } = new();
    public Dictionary<string, bool> MusicalTastesDictionary { get; set; } = new();
}