namespace Moodler.Helpers;

public class CategoriesHelper
{
    public List<string> GetDayTimes(string category)
    {
        var dayTimes = new List<string>
        {
            "Morning",
            "Afternoon",
            "Evening",
            "Night",
            "Dawn",
            "Dusk"
        };
        
        if(!dayTimes.Contains(category, StringComparer.OrdinalIgnoreCase))
            dayTimes.Add(category);
        
        return dayTimes.Order().ToList();
    }

    public List<string> GetMoods(string category)
    {
        var moods = new List<string>
        {
            "Joy",
            "Excited",
            "Grateful",
            "Hopeful",
            "Confident",
            "Relaxed",
            "Amused",
            "Loving",
            "Inspirational",
            "Sad",
            "Angry",
            "Cozy",
            "Bored",
            "Melancholic",
            "Tired",
            "Nostalgic",
        };
        
        if(!moods.Contains(category, StringComparer.OrdinalIgnoreCase))
            moods.Add(category);
        
        return moods.Order().ToList();
    }

    public List<string> GetRelationships(string category)
    {
        var relationships = new List<string>
        {
            "Family",
            "Friends",
            "Lovers",
            "Colleagues",
        };
        
        if(!relationships.Contains(category, StringComparer.OrdinalIgnoreCase))
            relationships.Add(category);

        return relationships.Order().ToList();
    }

    public List<string> GetMusicalTastes(string category)
    {
        var musicalTastes = new List<string>
        {
            "Pop",
            "Rock",
            "Hip-Hop",
            "Jazz",
            "Classical",
            "R&B",
            "Country",
            "Lo-Fi",
            "Techno",
            "Dubstep",
            "EDM",
            "Blues",
            "Reggae",
            "Metal",
            "Folk",
            "Indie",
            "Soul",
            "Punk",
            "Funk",
            "Alternative",
            "Disco",
            "Reggaeton",
            "Latin",
            "Gospel",
        };
        
        if(!musicalTastes.Contains(category, StringComparer.OrdinalIgnoreCase))
            musicalTastes.Add(category);
        
        return musicalTastes.Order().ToList();
    }
}